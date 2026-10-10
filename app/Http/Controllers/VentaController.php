<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\DetalleProducto;
use App\Models\HistorialVenta;
use App\Models\LoteInventario;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Traits\HandlesTableFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    use HandlesTableFeatures;

    public function index(Request $request)
    {
        $esAdmin = auth()->user()->can('roles.gestionar');
        $query   = Venta::with(['usuario', 'sucursal'])->latest();

        // Vendedor: solo ve ventas de su sucursal
        if (!$esAdmin) {
            $query->where('id_sucursal', auth()->user()->id_sucursal ?? 1);
        } else {
            // Admin: filtro opcional por sucursal
            if ($request->filled('sucursal')) {
                $query->where('id_sucursal', $request->sucursal);
            }
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }
        if ($request->filled('metodo_pago')) {
            $query->where('metodo_pago', $request->metodo_pago);
        }

        $totalFiltrado = (clone $query)->activas()->sum('total');

        if ($request->boolean('exportar')) {
            $items = (clone $query)->get();
            return $this->exportarCsv('ventas', $items, [
                'ID'          => fn($v) => $v->id,
                'Fecha'       => fn($v) => $v->created_at->format('d/m/Y H:i'),
                'Método pago' => fn($v) => $v->metodo_pago,
                'Total'       => fn($v) => number_format($v->total, 2, '.', ''),
                'Estado'      => fn($v) => $v->anulada ? 'Anulada' : 'Pagada',
                'Vendedor'    => fn($v) => $v->usuario?->nombre ?? '—',
            ]);
        }

        $ventas    = $query->paginate($this->getPerPage())->withQueryString();
        $sucursales = $esAdmin ? Sucursal::orderBy('nombre')->get() : collect();

        return view('ventas.index', compact('ventas', 'totalFiltrado', 'sucursales', 'esAdmin'));
    }

    public function create()
    {
        return view('ventas.create');
    }

    public function createMovil()
    {
        // Pre-carga de producto desde el escaneo
        $preloadBarcode = null;
        if (request()->filled('item')) {
            $detalle = DetalleProducto::find((int) request()->item);
            $preloadBarcode = $detalle?->codigo_barras;
        }

        return view('ventas.pos-movil', compact('preloadBarcode'));
    }

    public function hubMovil()
    {
        return view('ventas.pos-hub');
    }

    /**
     * Procesa y guarda una venta completa (efectivo, sin crédito, sin cliente).
     * Implementa lógica FIFO para descuento de inventario.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id_detalle_producto' => 'required|exists:detalle_productos,id',
            'items.*.cantidad'            => 'required|integer|min:1',
            'items.*.precio_venta'        => 'required|numeric|min:0|max:99999999',
            'observaciones'                => 'nullable|string|max:500',
        ]);

        $idBodegaActiva = auth()->user()->id_bodega ?? 1;
        $bodegaActiva = Bodega::find($idBodegaActiva);
        if (!$bodegaActiva || !$bodegaActiva->activo) {
            return back()->withInput()->with('error', 'Tu bodega asignada está desactivada o no existe.');
        }

        try { DB::transaction(function () use ($request, $idBodegaActiva) {
            // Validar stock de todos los ítems.
            // lockForUpdate() bloquea las filas de lotes durante la transacción
            // para evitar que ventas concurrentes lean el mismo stock y lo agoten doblemente.
            foreach ($request->items as $item) {
                $producto = DetalleProducto::with('producto')->findOrFail($item['id_detalle_producto']);
                $stockDisponible = LoteInventario::where('id_detalle_producto', $item['id_detalle_producto'])
                                                  ->conStock($idBodegaActiva)
                                                  ->lockForUpdate()
                                                  ->sum('cantidad_disponible');

                if ($stockDisponible < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para {$producto->producto->nombre} - {$producto->talla}. Disponible: {$stockDisponible}");
                }
            }

            $total = collect($request->items)->sum(fn($i) => $i['cantidad'] * $i['precio_venta']);

            $venta = Venta::create([
                'id_usuario'    => auth()->id(),
                'id_sucursal'   => auth()->user()->id_sucursal ?? 1,
                'metodo_pago'   => Venta::METODO_EFECTIVO,
                'total'         => $total,
                'observaciones' => $request->observaciones,
            ]);

            // Procesar cada ítem (descuento FIFO)
            foreach ($request->items as $item) {
                $detalle = DetalleProducto::with('producto')->findOrFail($item['id_detalle_producto']);
                $cantidadPendiente = $item['cantidad'];

                // lockForUpdate() asegura que no haya otra transacción paralela
                // modificando los mismos lotes entre el check y el decrement.
                $lotes = LoteInventario::where('id_detalle_producto', $detalle->id)
                                       ->conStock($idBodegaActiva)
                                       ->lockForUpdate()
                                       ->get();

                foreach ($lotes as $lote) {
                    if ($cantidadPendiente <= 0) break;

                    $cantidadADescontar = min($cantidadPendiente, $lote->cantidad_disponible);

                    HistorialVenta::create([
                        'id_venta'       => $venta->id,
                        'id_lote'        => $lote->id,
                        'cantidad'       => $cantidadADescontar,
                        'precio_venta'   => $item['precio_venta'],
                        'costo_unitario' => $lote->costo_unitario,
                    ]);

                    $lote->decrement('cantidad_disponible', $cantidadADescontar);

                    $cantidadPendiente -= $cantidadADescontar;
                }

                if ($cantidadPendiente > 0) {
                    throw new \Exception("Stock insuficiente para {$detalle->producto->nombre} - {$detalle->talla}: faltaron {$cantidadPendiente} unidad(es) al momento de descontar. Es posible que otra venta haya tomado el stock simultáneamente; intenta de nuevo.");
                }
            }

            session(['ultima_venta_id' => $venta->id]);
        }); } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $ventaId = session('ultima_venta_id');
        return redirect()->route('ventas.ticket', $ventaId)
                         ->with('success', 'Venta registrada correctamente.');
    }

    public function show(Venta $venta)
    {
        $venta->load(['usuario', 'historialVentas.lote.detalleProducto.producto']);
        return view('ventas.show', compact('venta'));
    }

    /**
     * Vista de ticket para imprimir
     */
    public function ticket(Venta $venta)
    {
        $venta->load(['usuario', 'historialVentas.lote.detalleProducto.producto']);
        return view('ventas.ticket', compact('venta'));
    }

    /**
     * Anula una venta y devuelve el stock (sin eliminar el registro)
     */
    public function destroy(Request $request, Venta $venta)
    {
        if ($venta->anulada) {
            return back()->with('error', 'Esta venta ya fue anulada.');
        }

        $request->validate([
            'motivo_anulacion' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $venta) {
            foreach ($venta->historialVentas()->with('lote')->get() as $item) {
                $item->lote->increment('cantidad_disponible', $item->cantidad);
            }

            // Marcar como anulada (no se elimina para conservar trazabilidad)
            $venta->update([
                'anulada'          => true,
                'motivo_anulacion' => $request->motivo_anulacion,
            ]);
        });

        return redirect()->route('ventas.index')
                         ->with('success', 'Venta anulada y stock restaurado. El registro se conserva para trazabilidad.');
    }
}
