@extends('layouts.app')

@section('title', 'Venta #' . $venta->id)
@section('page-title', 'Detalle Venta #' . str_pad($venta->id, 5, '0', STR_PAD_LEFT))

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('ventas.ticket', $venta) }}" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Ver Ticket
        </a>
        <a href="{{ route('ventas.index') }}" class="btn-secondary">← Volver</a>
    </div>

    <div class="grid md:grid-cols-2 gap-5">
        {{-- Info general --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Información General</h3></div>
            <div class="card-body space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Número</span><span class="font-bold">#{{ str_pad($venta->id, 5, '0', STR_PAD_LEFT) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Fecha</span><span>{{ $venta->created_at->format('d/m/Y H:i') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Vendedor</span><span>{{ $venta->usuario->nombre }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Método de pago</span>
                    <span class="badge badge-info">{{ $venta->metodo_pago }}</span>
                </div>
                <div class="flex justify-between items-center border-t pt-3">
                    <span class="text-gray-500">Estado</span>
                    @if($venta->anulada)
                        <span class="badge badge-danger">Anulada</span>
                    @else
                        <span class="badge badge-success">Pagado</span>
                    @endif
                </div>
                @if($venta->anulada)
                <div class="flex justify-between"><span class="text-gray-500">Motivo anulación</span><span class="text-gray-600 italic">{{ $venta->motivo_anulacion ?? 'No especificado' }}</span></div>
                @endif
            </div>
        </div>

        {{-- Totales --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Resumen Financiero</h3></div>
            <div class="card-body">
                <div class="space-y-2 text-sm">
                    @foreach($venta->historialVentas as $item)
                    <div class="flex justify-between">
                        <span class="text-gray-600">
                            {{ $item->lote->detalleProducto->producto->nombre }}
                            @if($item->lote->detalleProducto->talla)
                                ({{ $item->lote->detalleProducto->talla }})
                            @endif
                            × {{ $item->cantidad }}
                        </span>
                        <span class="text-money">${{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="border-t mt-4 pt-4">
                    <div class="flex justify-between text-lg font-extrabold">
                        <span>TOTAL</span>
                        <span class="text-money text-blue-700">${{ number_format($venta->total, 0, ',', '.') }}</span>
                    </div>
                    @php
                        $gananciaTotal = $venta->historialVentas->sum('ganancia');
                    @endphp
                    <div class="flex justify-between text-sm text-emerald-600 mt-1">
                        <span>Ganancia estimada</span>
                        <span class="text-money">${{ number_format($gananciaTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de productos vendidos --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Productos Vendidos</h3></div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Talla/SKU</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-right">Precio Venta</th>
                        <th class="text-right">Costo</th>
                        <th class="text-right">Subtotal</th>
                        <th class="text-right">Ganancia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->historialVentas as $item)
                    @php $det = $item->lote->detalleProducto; @endphp
                    <tr>
                        <td class="font-medium">{{ $det->producto->nombre }}</td>
                        <td class="text-gray-500">{{ $det->talla ?? '—' }}</td>
                        <td class="text-center text-money">{{ $item->cantidad }}</td>
                        <td class="text-right text-money">${{ number_format($item->precio_venta, 0, ',', '.') }}</td>
                        <td class="text-right text-gray-400 text-money">${{ number_format($item->costo_unitario, 0, ',', '.') }}</td>
                        <td class="text-right font-semibold text-money">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        <td class="text-right text-emerald-600 text-money">${{ number_format($item->ganancia, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
