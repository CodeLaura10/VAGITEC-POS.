@extends('layouts.mobile')

@section('title', 'POS Móvil')

@section('content')
<div
    x-data="posMovil('{{ route('productos.buscar-pos') }}')"
    class="flex flex-col min-h-screen pb-40"
>

    {{-- ══════ BARRA SUPERIOR ══════ --}}
    <header class="sticky top-0 z-30 bg-blue-700 text-white px-4 py-3 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-2.5">
            <svg class="w-6 h-6 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <div>
                <p class="text-sm font-bold leading-tight">POS Móvil</p>
                <p class="text-xs text-blue-200 leading-tight">{{ auth()->user()->nombre }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            {{-- Contador carrito --}}
            <span x-show="carrito.length > 0"
                class="bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-0.5 rounded-full"
                x-text="carrito.reduce((s,i) => s+i.cantidad, 0) + ' uds.'">
            </span>
            {{-- Ir a escritorio --}}
            @can('ventas.crear')
            <a href="{{ route('ventas.create') }}"
               class="text-blue-200 hover:text-white text-xs underline hidden sm:block">Escritorio</a>
            @endcan
            {{-- Cerrar sesión --}}
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-blue-200 hover:text-white p-1.5 rounded-lg hover:bg-blue-600 transition-colors" title="Cerrar sesión">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </header>

    {{-- ══════ FORMULARIO PRINCIPAL ══════ --}}
    <form id="form-movil" method="POST" action="{{ route('ventas.store') }}" class="flex flex-col flex-1 px-3 pt-3 gap-3">
        @csrf

        {{-- ── OBSERVACIONES ───────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3">
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold text-gray-500 flex-shrink-0 w-16">Notas</label>
                <input type="text" name="observaciones" placeholder="Observaciones..."
                    class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-300 min-w-0">
            </div>
        </div>

        {{-- ── BOTÓN ESCANEAR + BÚSQUEDA ────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 space-y-2">

            {{-- Botón principal de escaneo --}}
            <button type="button" @click="abrirScanner()"
                class="w-full py-5 rounded-2xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-lg flex items-center justify-center gap-3 shadow-md transition-colors select-none">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.24M16.57 9H14m2.86-4.93A9 9 0 105.4 18.6M15 15l3 3"/>
                    <rect x="3" y="3" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                    <rect x="3" y="16" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                    <rect x="16" y="3" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                </svg>
                Escanear con cámara
            </button>

            {{-- Búsqueda manual --}}
            <div class="flex gap-2">
                <input type="text" x-model="busqueda"
                    @input.debounce.400ms="buscarManual()"
                    @keydown.escape="resultados = []"
                    placeholder="O escribe nombre / código..."
                    autocomplete="off"
                    class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-300">
                <button type="button" @click="buscarManual()"
                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-xl text-gray-600 active:bg-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>

            {{-- Resultados búsqueda --}}
            <div x-show="resultados.length > 0"
                class="border border-gray-200 rounded-xl overflow-hidden shadow-lg bg-white">
                <div class="flex items-center justify-between px-3 py-1.5 bg-gray-50 border-b border-gray-100">
                    <span class="text-xs text-gray-500 font-medium" x-text="resultados.length + ' resultado(s)'"></span>
                    <button type="button" @click="resultados = []; busqueda = ''"
                        class="text-xs text-gray-400 hover:text-gray-600">Cerrar</button>
                </div>
                <template x-for="producto in resultados" :key="producto.id">
                    <div class="border-b border-gray-50 last:border-0 px-3 py-2.5"
                         :class="producto.stock === 0 ? 'bg-amber-50' : ''">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 leading-tight" x-text="producto.producto"></p>
                                <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                    <span x-show="producto.talla" class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-medium" x-text="'T ' + producto.talla"></span>
                                    <span class="text-xs font-bold text-gray-700">$<span x-text="producto.precio.toLocaleString('es-CO')"></span></span>
                                    <span x-show="producto.stock > 0"
                                        class="text-xs"
                                        :class="producto.stock <= 3 ? 'text-amber-600 font-semibold' : 'text-gray-400'"
                                        x-text="'Stock: ' + producto.stock"></span>
                                    <span x-show="producto.stock === 0" class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">Sin stock</span>
                                </div>
                            </div>
                            <div class="flex gap-1.5 flex-shrink-0">
                                <button type="button" x-show="producto.stock > 0"
                                    @click="agregarProducto(producto); resultados = []; busqueda = ''"
                                    class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-xl active:bg-blue-700">
                                    + Agregar
                                </button>
                                <span x-show="producto.stock === 0"
                                    class="text-xs text-gray-400 italic self-center">Sin stock</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ── CARRITO ──────────────────────────────────────────────────────────── --}}

        {{-- Vacío --}}
        <template x-if="carrito.length === 0">
            <div class="bg-white rounded-2xl border border-dashed border-gray-200 py-10 text-center text-gray-300 shadow-sm">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-sm font-medium text-gray-400">Carrito vacío</p>
                <p class="text-xs text-gray-300 mt-1">Escanea un código o busca un producto</p>
            </div>
        </template>

        {{-- Items del carrito --}}
        <template x-if="carrito.length > 0">
            <div class="space-y-2">
                <div class="flex items-center justify-between px-1">
                    <h2 class="text-sm font-bold text-gray-700">
                        Carrito (<span x-text="carrito.length"></span> ítem<span x-show="carrito.length !== 1">s</span>)
                    </h2>
                    <button type="button" @click="limpiarCarrito()"
                        class="text-xs text-red-400 hover:text-red-600 font-medium">Limpiar</button>
                </div>

                <template x-for="(item, index) in carrito" :key="item.id + '-' + index">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 space-y-2.5">

                        {{-- Nombre + Eliminar --}}
                        <div class="flex items-start gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <p class="text-sm font-bold text-gray-900 leading-tight" x-text="item.producto"></p>
                                    <span x-show="item.talla" class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded" x-text="'T' + item.talla"></span>
                                </div>
                            </div>
                            <button type="button" @click="quitarProducto(index)"
                                class="flex-shrink-0 w-8 h-8 rounded-xl bg-red-50 hover:bg-red-100 text-red-400 flex items-center justify-center active:bg-red-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Precio + Cantidad + Subtotal --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            {{-- Precio unit --}}
                            <div class="flex items-center gap-1 flex-1 min-w-28">
                                <span class="text-xs text-gray-400 flex-shrink-0">$</span>
                                <input type="text" inputmode="numeric"
                                    :value="fmtMiles(item.precio)"
                                    @input="applyMiles($event, v => item.precio = Math.max(0, v))"
                                    class="flex-1 text-sm font-bold border border-gray-200 rounded-xl px-2 py-1.5 focus:ring-2 focus:ring-blue-300 text-center min-w-0"
                                    :class="item.costo > 0 && item.precio < item.costo ? 'border-amber-400 bg-amber-50 text-amber-700' : 'bg-white text-gray-900'">
                            </div>

                            {{-- Cantidad --}}
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button type="button" @click="cambiarCantidad(index, -1)"
                                    class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 active:bg-gray-300 flex items-center justify-center text-gray-600 font-bold text-lg select-none">−</button>
                                <span class="w-8 text-center text-sm font-extrabold text-gray-900" x-text="item.cantidad"></span>
                                <button type="button" @click="cambiarCantidad(index, 1)"
                                    class="w-9 h-9 rounded-full bg-blue-100 hover:bg-blue-200 active:bg-blue-300 flex items-center justify-center text-blue-700 font-bold text-lg select-none">+</button>
                            </div>

                            {{-- Subtotal --}}
                            <div class="flex-shrink-0 text-right">
                                <p class="text-xs text-gray-400">Subtotal</p>
                                <p class="text-sm font-extrabold text-gray-900">$<span x-text="(item.cantidad * item.precio).toLocaleString('es-CO')"></span></p>
                            </div>
                        </div>

                        {{-- Hidden inputs --}}
                        <input type="hidden" :name="'items[' + index + '][id_detalle_producto]'" :value="item.id">
                        <input type="hidden" :name="'items[' + index + '][precio_venta]'"        :value="item.precio">
                        <input type="hidden" :name="'items[' + index + '][cantidad]'"            :value="item.cantidad">
                    </div>
                </template>
            </div>
        </template>

    </form>{{-- /form-movil --}}

    {{-- ══════ BARRA FIJA INFERIOR ══════ --}}
    <div class="fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-gray-200 shadow-2xl safe-area-bottom">
        <div class="px-3 py-2 space-y-2">

            {{-- Efectivo recibido --}}
            <template x-if="carrito.length > 0">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 flex-shrink-0">Recibido:</span>
                    <div class="relative flex-1">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">$</span>
                        <input type="text" inputmode="numeric"
                            :value="fmtMiles(efectivoPagado)"
                            @input="applyMiles($event, v => efectivoPagado = v)"
                            class="w-full pl-7 pr-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-300">
                    </div>
                    <div x-show="efectivoPagado >= total && total > 0" class="flex items-center gap-1 flex-shrink-0">
                        <span class="text-xs text-gray-500">Cambio:</span>
                        <span class="text-sm font-bold text-emerald-600">$<span x-text="cambio.toLocaleString('es-CO')"></span></span>
                    </div>
                </div>
            </template>

            {{-- Total + Registrar --}}
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <p class="text-xs text-gray-400">Total</p>
                    <p class="text-2xl font-extrabold text-gray-900 leading-tight">
                        $<span x-text="total.toLocaleString('es-CO')"></span>
                    </p>
                </div>
                <button type="submit" form="form-movil"
                    @click.prevent="if(prepararFormulario()) document.getElementById('form-movil').submit()"
                    :disabled="carrito.length === 0"
                    class="flex-shrink-0 px-6 py-4 rounded-2xl font-bold text-base text-white shadow-lg transition-colors active:scale-95
                        bg-emerald-500 hover:bg-emerald-400 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100
                        flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Registrar Venta
                </button>
            </div>
        </div>
    </div>

    {{-- ══════ MODAL SCANNER ══════ --}}
    <div x-show="scannerModal"
         class="fixed inset-0 z-50 flex flex-col bg-black"
         style="display:none"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">

        {{-- Header scanner --}}
        <div class="flex items-center justify-between px-4 py-3 bg-black/80 text-white flex-shrink-0">
            <div>
                <p class="font-bold text-base">Escanear código</p>
                <p class="text-xs text-gray-400">Apunta la cámara al código de barras</p>
            </div>
            <button type="button" @click="cerrarScanner()"
                class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white active:bg-white/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Visor cámara --}}
        <div class="relative flex-1 flex items-center justify-center overflow-hidden bg-black">
            <div id="qr-reader" class="w-full max-w-sm"></div>

            {{-- Overlay indicador --}}
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="border-2 border-blue-400 rounded-xl w-72 h-28 relative">
                    <div class="absolute -top-0.5 -left-0.5 w-6 h-6 border-t-4 border-l-4 border-blue-400 rounded-tl-xl"></div>
                    <div class="absolute -top-0.5 -right-0.5 w-6 h-6 border-t-4 border-r-4 border-blue-400 rounded-tr-xl"></div>
                    <div class="absolute -bottom-0.5 -left-0.5 w-6 h-6 border-b-4 border-l-4 border-blue-400 rounded-bl-xl"></div>
                    <div class="absolute -bottom-0.5 -right-0.5 w-6 h-6 border-b-4 border-r-4 border-blue-400 rounded-br-xl"></div>
                </div>
            </div>
        </div>

        {{-- Búsqueda manual desde scanner --}}
        <div class="bg-black/90 px-4 py-3 flex-shrink-0 space-y-2">
            <div class="flex gap-2">
                <input type="text" x-model="busqueda"
                    placeholder="O escribe el código manualmente..."
                    @keydown.enter.prevent="buscarPorCodigo(busqueda)"
                    class="flex-1 text-sm bg-white/10 border border-white/20 text-white placeholder-gray-500 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-400 focus:bg-white/20">
                <button type="button" @click="buscarPorCodigo(busqueda)"
                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold text-sm active:bg-blue-700">
                    Buscar
                </button>
            </div>

            {{-- Resultados dentro del scanner --}}
            <div x-show="resultados.length > 0" class="space-y-1.5 max-h-60 overflow-y-auto">
                <template x-for="producto in resultados" :key="producto.id">
                    <div class="bg-white/10 rounded-xl px-3 py-2.5 flex items-center justify-between gap-2"
                         :class="producto.stock === 0 ? 'border border-amber-500/40' : ''">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-white leading-tight" x-text="producto.producto"></p>
                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                <span x-show="producto.talla" class="text-xs text-gray-400" x-text="'T' + producto.talla"></span>
                                <span class="text-xs font-bold text-blue-300">$<span x-text="producto.precio.toLocaleString('es-CO')"></span></span>
                                <span x-show="producto.stock > 0" class="text-xs text-gray-400" x-text="'Stock: ' + producto.stock"></span>
                                <span x-show="producto.stock === 0" class="text-xs text-amber-400 font-semibold">Sin stock</span>
                            </div>
                        </div>
                        <div class="flex gap-1.5 flex-shrink-0">
                            <button type="button" x-show="producto.stock > 0"
                                @click="agregarProducto(producto); cerrarScanner()"
                                class="px-3 py-2 bg-emerald-500 text-white text-xs font-bold rounded-xl active:bg-emerald-600">
                                + Agregar
                            </button>
                        </div>
                    </div>
                </template>
                <button type="button" @click="reanudarScanner()"
                    class="w-full py-2 text-xs text-blue-300 hover:text-blue-200 font-medium">
                    ↺ Escanear otro código
                </button>
            </div>

            {{-- Spinner búsqueda --}}
            <div x-show="buscando" class="flex items-center justify-center gap-2 py-2">
                <svg class="animate-spin w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-xs text-gray-400">Buscando...</span>
            </div>
        </div>
    </div>{{-- /modal scanner --}}

</div>{{-- /x-data --}}

@push('styles')
<style>
    /* Fuerza el visor de la cámara a ocupar bien el espacio */
    #qr-reader {
        width: 100% !important;
        border: none !important;
    }
    #qr-reader video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
    }
    #qr-reader__scan_region {
        min-height: 250px;
    }
    #qr-reader__dashboard {
        display: none !important;
    }
    /* Espacio safe area para notch/home indicator */
    .safe-area-bottom {
        padding-bottom: env(safe-area-inset-bottom, 8px);
    }
    .pb-safe {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 1.25rem);
    }
</style>
@endpush
@endsection
