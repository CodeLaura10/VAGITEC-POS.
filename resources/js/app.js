import './bootstrap';
import Alpine from 'alpinejs';
import { Html5Qrcode } from 'html5-qrcode';
window.Html5Qrcode = Html5Qrcode;

window.Alpine = Alpine;

// ─── Toast Notifications ─────────────────────────────────────────────────────

window.showToast = function(message, type = 'success', duration = 4000) {
    const container = document.getElementById('toast-container') || createToastContainer();

    const icons = {
        success: `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`,
        error: `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`,
        warning: `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`,
        info: `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        ${icons[type] || icons.info}
        <span class="flex-1">${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-2 opacity-70 hover:opacity-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'all 0.3s ease-out';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, duration);
};

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

// ─── Confirmar Eliminación ────────────────────────────────────────────────────

window.confirmarEliminar = function(formId, mensaje = '¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.') {
    if (confirm(mensaje)) {
        document.getElementById(formId).submit();
    }
};

// ─── Separador de miles para inputs ──────────────────────────────────────────

window.fmtMiles = function(n) {
    const num = parseInt(String(n ?? '').replace(/\D/g, ''), 10);
    return (isNaN(num) || num === 0) ? '' : new Intl.NumberFormat('es-CO').format(num);
};

// Actualiza un input type="text" con miles y mantiene la variable Alpine sincronizada
window.applyMiles = function(event, setter) {
    const digits = event.target.value.replace(/\D/g, '');
    const n = parseInt(digits, 10);
    const val = isNaN(n) ? 0 : n;
    setter(val);
    event.target.value = val ? window.fmtMiles(val) : '';
};

// ─── URL Base del sistema (funciona en subdirectorio XAMPP) ──────────────────

function getBaseUrl() {
    return document.querySelector('meta[name="base-url"]')?.content
        || window.location.origin;
}

// ─── Alpine.js Data Components ───────────────────────────────────────────────

document.addEventListener('alpine:init', () => {

    // POS Móvil: escaneo con cámara + venta normal en efectivo
    Alpine.data('posMovil', (buscarUrl) => ({
        buscarUrl: buscarUrl || (getBaseUrl() + '/api/productos/buscar'),

        // Carrito
        carrito: [],
        observaciones: '',
        efectivoPagado: 0,

        // Scanner
        escaner: null,
        scannerActivo: false,
        scannerModal: false,
        procesandoScan: false,
        busqueda: '',
        resultados: [],
        buscando: false,

        get total() {
            return this.carrito.reduce((sum, i) => sum + i.cantidad * i.precio, 0);
        },
        get cambio() {
            return Math.max(0, this.efectivoPagado - this.total);
        },

        // ── Scanner ──────────────────────────────────────────────────────────────

        async abrirScanner() {
            this.scannerModal = true;
            this.resultados = [];
            this.busqueda = '';
            await this.$nextTick();
            this.iniciarScanner();
        },

        iniciarScanner() {
            if (this.scannerActivo) return;
            this.escaner = new window.Html5Qrcode('qr-reader');
            this.escaner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 260, height: 120 } },
                async (codigo) => {
                    if (this.procesandoScan) return;
                    this.procesandoScan = true;
                    this.busqueda = codigo;
                    await this.buscarPorCodigo(codigo);
                    setTimeout(() => { this.procesandoScan = false; }, 2000);
                },
                () => {}
            ).then(() => {
                this.scannerActivo = true;
            }).catch((err) => {
                showToast('No se pudo acceder a la cámara: ' + err, 'error');
                this.cerrarScanner();
            });
        },

        cerrarScanner() {
            if (this.escaner && this.scannerActivo) {
                this.escaner.stop().catch(() => {});
                this.escaner = null;
                this.scannerActivo = false;
            }
            this.scannerModal = false;
        },

        async buscarPorCodigo(codigo) {
            this.buscando = true;
            try {
                const res = await fetch(`${this.buscarUrl}?q=${encodeURIComponent(codigo)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                const exacto = data.find(p => p.codigo_barras === codigo.trim());
                if (exacto) {
                    if (exacto.stock === 0) {
                        this.resultados = data;
                        showToast('Sin stock disponible', 'warning', 3000);
                        this.detenerEscanerTemporal();
                    } else {
                        this.agregarProducto(exacto);
                        this.cerrarScanner();
                        showToast(`${exacto.producto} - ${exacto.talla} agregado`, 'success', 2000);
                    }
                } else if (data.length > 0) {
                    this.resultados = data;
                    this.detenerEscanerTemporal();
                } else {
                    showToast('Producto no encontrado', 'warning');
                }
            } catch {
                showToast('Error al buscar producto', 'error');
            } finally {
                this.buscando = false;
            }
        },

        async buscarManual() {
            if (this.busqueda.length < 2) { this.resultados = []; return; }
            await this.buscarPorCodigo(this.busqueda);
        },

        detenerEscanerTemporal() {
            if (this.escaner && this.scannerActivo) {
                this.escaner.stop().catch(() => {});
                this.scannerActivo = false;
            }
        },

        reanudarScanner() {
            this.resultados = [];
            this.busqueda = '';
            this.iniciarScanner();
        },

        // ── Carrito ──────────────────────────────────────────────────────────────

        agregarProducto(producto) {
            if (producto.stock === 0) {
                showToast('Sin stock disponible', 'warning');
                return;
            }
            const existente = this.carrito.find(i => i.id === producto.id);
            if (existente) {
                if (existente.cantidad < producto.stock) {
                    existente.cantidad++;
                } else {
                    showToast('Stock insuficiente', 'warning');
                }
            } else {
                this.carrito.push({
                    id: producto.id, producto: producto.producto, talla: producto.talla,
                    precio: producto.precio, precioBase: producto.precio,
                    costo: producto.costo || 0, cantidad: 1, stock: producto.stock,
                });
            }
        },

        quitarProducto(index) {
            this.carrito.splice(index, 1);
        },

        cambiarCantidad(index, delta) {
            const item = this.carrito[index];
            const nueva = item.cantidad + delta;
            if (nueva <= 0) {
                this.carrito.splice(index, 1);
            } else if (nueva <= item.stock) {
                item.cantidad = nueva;
            } else {
                showToast('Stock insuficiente', 'warning');
            }
        },

        limpiarCarrito() {
            if (confirm('¿Limpiar el carrito?')) {
                this.carrito = [];
            }
        },

        prepararFormulario() {
            if (this.carrito.length === 0) {
                showToast('El carrito está vacío', 'warning');
                return false;
            }
            return true;
        },
    }));

    Alpine.data('sidebar', () => ({
        abierto: localStorage.getItem('sidebarAbierto') !== 'false',
        tooltip: { visible: false, text: '', top: 0, left: 0 },

        toggle() {
            this.abierto = !this.abierto;
            localStorage.setItem('sidebarAbierto', this.abierto);
            if (this.abierto) this.tooltip.visible = false;
        },

        mostrarTooltip(event) {
            if (this.abierto) return;
            const el = event.currentTarget;
            const text = el.dataset.tooltip;
            if (!text) return;
            const rect = el.getBoundingClientRect();
            this.tooltip = {
                visible: true,
                text,
                top: Math.round(rect.top + rect.height / 2),
                left: Math.round(rect.right + 10),
            };
        },

        ocultarTooltip() {
            this.tooltip.visible = false;
        },
    }));
});

Alpine.start();

// ─── Auto-dismiss Flash Messages ─────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const flashes = document.querySelectorAll('[data-flash]');
    flashes.forEach(el => {
        const type = el.dataset.flash;
        const message = el.dataset.message;
        if (message) {
            showToast(message, type);
        }
        el.remove();
    });
});
