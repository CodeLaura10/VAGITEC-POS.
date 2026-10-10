@extends('layouts.app')

@section('title', 'Nueva Venta - POS')
@section('page-title', 'Punto de Venta')

@section('content')
<div x-data="{
        buscarUrl: '{{ route('productos.buscar-pos') }}',
        busqueda: '', resultados: [], buscando: false,
        carrito: [], efectivoPagado: 0,
        get total() { return this.carrito.reduce((s,i) => s + i.cantidad * i.precio, 0); },
        get cambio() { return Math.max(0, this.efectivoPagado - this.total); },
        async buscarProducto() {
            if (this.busqueda.length < 2) { this.resultados = []; return; }
            this.buscando = true;
            try {
                const res = await fetch(`${this.buscarUrl}?q=${encodeURIComponent(this.busqueda)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                this.resultados = res.ok ? await res.json() : [];
            } finally { this.buscando = false; }
        },
        agregarProducto(producto) {
            if (producto.stock === 0) { showToast('Sin stock disponible', 'warning'); return; }
            const existente = this.carrito.find(i => i.id === producto.id);
            if (existente) {
                if (existente.cantidad < producto.stock) existente.cantidad++;
                else showToast('Stock insuficiente', 'warning');
            } else {
                this.carrito.push({ id: producto.id, producto: producto.producto, talla: producto.talla,
                    precio: producto.precio, precioBase: producto.precio, costo: producto.costo || 0,
                    cantidad: 1, stock: producto.stock });
            }
        },
        quitarProducto(index) { this.carrito.splice(index, 1); },
        cambiarCantidad(index, delta) {
            const item = this.carrito[index];
            const nueva = item.cantidad + delta;
            if (nueva <= 0) this.carrito.splice(index, 1);
            else if (nueva <= item.stock) item.cantidad = nueva;
            else showToast('Stock insuficiente', 'warning');
        },
        limpiarCarrito() { if (confirm('¿Limpiar el carrito?')) this.carrito = []; },
        prepararFormulario() {
            if (this.carrito.length === 0) { showToast('El carrito está vacío', 'warning'); return false; }
            return true;
        },
    }" class="space-y-4 pb-24">

    <form id="form-venta" method="POST" action="{{ route('ventas.store') }}">
        @csrf

        {{-- ══════ CABECERA COMPACTA ══════ --}}
        <div class="card">
            <div class="card-body py-3">
                <div class="flex flex-wrap items-center gap-4">
                    {{-- Observaciones --}}
                    <div class="flex items-center gap-2 flex-1 min-w-48">
                        <span class="text-xs text-gray-500 flex-shrink-0">Obs.:</span>
                        <input type="text" name="observaciones" placeholder="Notas adicionales..."
                            class="flex-1 text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-300 min-w-0">
                    </div>

                    {{-- Bodega activa del usuario --}}
                    @if(auth()->user()->bodega)
                    <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 flex-shrink-0">Bodega:</span>
                        <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded-lg">
                            {{ auth()->user()->bodega->nombre }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ══════ BÚSQUEDA ══════ --}}
        <div class="card">
            <div class="card-body py-3">
                <div class="relative">
                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        x-ref="inputBusqueda"
                        x-model="busqueda"
                        x-init="$nextTick(() => $refs.inputBusqueda.focus())"
                        @input.debounce.300ms="buscarProducto()"
                        @keydown.escape="resultados = []"
                        class="form-input pl-10 pr-24"
                        placeholder="Nombre del producto o código de barras..."
                        autocomplete="off"
                    />
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                        <template x-if="buscando">
                            <svg class="animate-spin w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </template>
                        <span x-show="carrito.length > 0"
                            class="text-xs bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded-full"
                            x-text="carrito.length + ' ítem' + (carrito.length !== 1 ? 's' : '')">
                        </span>
                    </div>
                </div>

                {{-- Dropdown resultados --}}
                <div x-show="resultados.length > 0"
                    class="mt-1 border border-gray-200 rounded-xl overflow-hidden shadow-lg bg-white z-20 relative">
                    <template x-for="producto in resultados" :key="producto.id">
                        <div class="border-b border-gray-50 last:border-0"
                             :class="producto.stock === 0 ? 'bg-amber-50' : ''">
                            <div class="flex items-center justify-between px-4 py-2.5 hover:bg-blue-50 transition-colors group">
                                <div class="min-w-0 flex-1 cursor-pointer"
                                    @click="if(producto.stock > 0) { agregarProducto(producto); $refs.inputBusqueda.focus(); }">
                                    <span class="text-sm font-medium text-gray-900" x-text="producto.producto"></span>
                                    <span x-show="producto.talla" class="ml-2 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded" x-text="producto.talla"></span>
                                </div>
                                <div class="flex items-center gap-3 ml-3 flex-shrink-0">
                                    <span class="text-xs font-mono text-gray-400" x-text="producto.codigo_barras"></span>
                                    <span class="text-xs font-bold text-gray-700 text-money">$<span x-text="producto.precio.toLocaleString('es-CO')"></span></span>
                                    <span x-show="producto.stock > 0" class="text-xs" :class="producto.stock <= 3 ? 'text-amber-600 font-medium' : 'text-gray-400'">
                                        Stock: <span x-text="producto.stock"></span>
                                    </span>
                                    <span x-show="producto.stock === 0" class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">Sin stock</span>
                                    <span x-show="producto.stock > 0" class="text-xs bg-blue-500 text-white px-2 py-0.5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer"
                                        @click.stop="agregarProducto(producto); $refs.inputBusqueda.focus()">+ Agregar</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ══════ TABLA DE PRODUCTOS ══════ --}}
        <div class="card">

            {{-- Vacío --}}
            <template x-if="carrito.length === 0">
                <div class="text-center py-16 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="font-medium text-sm">El carrito está vacío</p>
                    <p class="text-xs text-gray-300 mt-1">Busca productos por nombre o código de barras</p>
                </div>
            </template>

            {{-- Tabla --}}
            <template x-if="carrito.length > 0">
                <div>
                    <div class="card-header border-b border-gray-100">
                        <h3 class="card-title">Productos en venta</h3>
                        <button type="button" @click="limpiarCarrito()" class="btn btn-secondary btn-sm">Limpiar todo</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="text-left px-4 py-2.5">#</th>
                                    <th class="text-left px-4 py-2.5">Producto</th>
                                    <th class="text-center px-3 py-2.5 w-36">Precio unit.</th>
                                    <th class="text-center px-3 py-2.5 w-32">Cantidad</th>
                                    <th class="text-right px-4 py-2.5 w-28">Subtotal</th>
                                    <th class="w-10 px-2 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in carrito" :key="item.id">
                                    <tr class="border-b border-gray-50 hover:bg-gray-50/60 transition-colors">
                                        <td class="px-4 py-2.5 text-xs text-gray-400 font-mono" x-text="index + 1"></td>
                                        <td class="px-4 py-2.5">
                                            <p class="font-semibold text-gray-900 leading-tight" x-text="item.producto"></p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span x-show="item.talla" class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-medium" x-text="'Talla: ' + item.talla"></span>
                                                <span x-show="item.costo > 0 && item.precio < item.costo" class="text-xs text-amber-600 font-medium">⚠ Bajo costo</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <div class="relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-medium">$</span>
                                                <input type="text" inputmode="numeric"
                                                    :value="fmtMiles(item.precio)"
                                                    @input="applyMiles($event, v => item.precio = Math.max(0, v))"
                                                    class="w-full pl-6 pr-1 py-1.5 text-sm font-bold border rounded-lg focus:ring-2 focus:ring-blue-300 focus:border-blue-400 text-money"
                                                    :class="item.costo > 0 && item.precio < item.costo ? 'border-amber-400 bg-amber-50 text-amber-700' : 'border-gray-200 bg-white text-gray-900'">
                                            </div>
                                            <p x-show="item.precio !== item.precioBase"
                                               class="text-xs text-blue-500 mt-0.5 cursor-pointer hover:underline text-center"
                                               @click="item.precio = item.precioBase">
                                                Base: $<span x-text="item.precioBase.toLocaleString('es-CO')"></span>
                                            </p>
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" @click="cambiarCantidad(index, -1)"
                                                    class="w-7 h-7 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center text-gray-600 font-bold transition-colors text-sm">−</button>
                                                <span class="w-8 text-center text-sm font-bold text-gray-900 text-money" x-text="item.cantidad"></span>
                                                <button type="button" @click="cambiarCantidad(index, 1)"
                                                    class="w-7 h-7 rounded-full bg-blue-100 hover:bg-blue-200 flex items-center justify-center text-blue-700 font-bold transition-colors text-sm">+</button>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-extrabold text-gray-900 text-money">
                                            $<span x-text="(item.cantidad * item.precio).toLocaleString('es-CO')"></span>
                                        </td>
                                        <td class="px-2 py-2.5 text-center">
                                            <button type="button" @click="quitarProducto(index)"
                                                class="text-gray-300 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </td>
                                        <input type="hidden" :name="'items[' + index + '][id_detalle_producto]'" :value="item.id">
                                        <input type="hidden" :name="'items[' + index + '][precio_venta]'"        :value="item.precio">
                                        <input type="hidden" :name="'items[' + index + '][cantidad]'"            :value="item.cantidad">
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>

    </form>

    {{-- ══════ BARRA FIJA INFERIOR ══════ --}}
    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-lg">
        <div class="max-w-screen-xl mx-auto px-4 py-3 flex items-center justify-between gap-4 flex-wrap">

            <div class="flex items-center gap-6 text-sm min-w-0">
                <div class="hidden sm:flex items-center gap-1 text-gray-500">
                    <span class="font-semibold text-gray-900" x-text="carrito.length"></span>
                    <span class="text-xs">productos ·</span>
                    <span class="font-semibold text-gray-900" x-text="carrito.reduce((s,i) => s + i.cantidad, 0)"></span>
                    <span class="text-xs">uds.</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-gray-400">Total:</span>
                    <span class="font-extrabold text-gray-900 text-money text-lg">$<span x-text="total.toLocaleString('es-CO')"></span></span>
                </div>

                <template x-if="carrito.length > 0">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-gray-400">Recibido:</span>
                            <div class="relative">
                                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs">$</span>
                                <input type="text" inputmode="numeric"
                                    :value="fmtMiles(efectivoPagado)"
                                    @input="applyMiles($event, v => efectivoPagado = v)"
                                    class="w-32 pl-5 pr-2 py-1 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-300 text-money">
                            </div>
                        </div>
                        <div x-show="efectivoPagado >= total && total > 0" class="flex items-center gap-1.5">
                            <span class="text-xs text-gray-400">Cambio:</span>
                            <span class="font-bold text-emerald-600 text-money">$<span x-text="cambio.toLocaleString('es-CO')"></span></span>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('dashboard') }}"
                    class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                    Cancelar
                </a>
                <button type="submit" form="form-venta"
                    @click.prevent="if(prepararFormulario()) document.getElementById('form-venta').submit()"
                    :disabled="carrito.length === 0"
                    class="bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold px-6 py-2 rounded-xl text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Registrar Venta
                </button>
            </div>
        </div>
    </div>{{-- /barra fija --}}

</div>{{-- /x-data --}}

@endsection
