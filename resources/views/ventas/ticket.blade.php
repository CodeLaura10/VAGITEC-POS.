<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ticket #{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</title>
<style>
    @page {
        size: 80mm auto;
        margin: 4mm 3mm;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 12px;
        color: #000;
        background: #fff;
        width: 80mm;
        max-width: 80mm;
    }

    .center  { text-align: center; }
    .right   { text-align: right; }
    .bold    { font-weight: bold; }
    .small   { font-size: 10px; }
    .large   { font-size: 15px; }
    .xlarge  { font-size: 18px; }

    .sep-dashed  { border-top: 1px dashed #000; margin: 5px 0; }
    .sep-solid   { border-top: 2px solid #000; margin: 6px 0; }

    .row { display: flex; justify-content: space-between; margin: 2px 0; }
    .row .label { color: #444; }

    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 2px 0; font-size: 11px; }
    th { border-bottom: 1px dashed #000; text-align: left; font-size: 10px; }
    td.qty  { width: 28px; }
    td.price { width: 55px; text-align: right; }
    td.sub  { width: 65px; text-align: right; font-weight: bold; }

    .total-row { display: flex; justify-content: space-between; font-size: 16px; font-weight: bold; margin: 4px 0; }

    /* Botones de pantalla — se ocultan al imprimir */
    .no-print { background: #f3f4f6; padding: 10px; text-align: center; }
    .no-print button, .no-print a {
        display: inline-block;
        margin: 3px 5px;
        padding: 7px 16px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        font-size: 13px;
        cursor: pointer;
        text-decoration: none;
        color: #374151;
        background: #fff;
    }
    .no-print .btn-primary { background: #1d4ed8; color: #fff; border-color: #1d4ed8; }
    .no-print .btn-success  { background: #16a34a; color: #fff; border-color: #16a34a; }

    @media print {
        .no-print { display: none !important; }
        body { width: 80mm; }
    }
</style>
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 400);
    });
</script>
</head>
<body>

{{-- ── Controles de pantalla (no se imprimen) ── --}}
<div class="no-print">
    <button class="btn-primary" onclick="window.print()">🖨️ Imprimir de nuevo</button>
    <a href="{{ route('ventas.create') }}" class="btn-success">+ Nueva Venta</a>
    <a href="{{ route('ventas.show', $venta) }}">Ver detalle</a>
    <a href="{{ route('ventas.index') }}">Todas las ventas</a>
</div>

{{-- ══════════════════════════════════════
     TICKET  (esto es lo que se imprime)
══════════════════════════════════════ --}}

<div class="center">
    <div class="xlarge bold">VISOFTEC POS</div>
</div>

<div class="sep-solid"></div>

<div class="center small bold">** COMPROBANTE DE VENTA **</div>

<div class="sep-dashed"></div>

{{-- Datos de la venta --}}
<div class="row"><span class="label">Ticket N°</span><span class="bold">{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</span></div>
<div class="row"><span class="label">Fecha</span><span>{{ $venta->created_at->format('d/m/Y H:i') }}</span></div>
<div class="row"><span class="label">Atendió</span><span>{{ $venta->usuario->nombre }}</span></div>
<div class="row"><span class="label">Método pago</span><span>{{ $venta->metodo_pago }}</span></div>

<div class="sep-dashed"></div>

{{-- Productos --}}
<table>
    <thead>
        <tr>
            <th>Descripción</th>
            <th class="right" style="width:28px">Cant</th>
            <th class="right" style="width:60px">V.Unit</th>
            <th class="right" style="width:65px">Total</th>
        </tr>
    </thead>
    <tbody>
    @foreach($venta->historialVentas as $item)
    @php
        $detalle  = $item->lote->detalleProducto;
        $producto = $detalle->producto;
        $nombre   = $producto->nombre . ($detalle->talla ? ' (' . $detalle->talla . ')' : '');
    @endphp
    <tr>
        <td>{{ $nombre }}</td>
        <td class="qty right">{{ $item->cantidad }}</td>
        <td class="price">${{ number_format($item->precio_venta, 0, ',', '.') }}</td>
        <td class="sub">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="sep-solid"></div>

{{-- Total --}}
<div class="total-row">
    <span>TOTAL</span>
    <span>${{ number_format($venta->total, 0, ',', '.') }}</span>
</div>

<div class="sep-solid"></div>

<div class="center small">
    ¡Gracias por su compra!<br>
    Conserve este comprobante
</div>

@if($venta->observaciones)
<div class="sep-dashed"></div>
<div class="small center">Obs: {{ $venta->observaciones }}</div>
@endif

<div class="sep-dashed"></div>
<div class="center small" style="color:#555;">
    VISOFTEC POS · Ticket generado {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
