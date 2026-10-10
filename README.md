# VISOFTEC POS — HU-001 / HU-002

Copia reducida y funcional del proyecto VISOFTEC POS, con únicamente el código de:

- **HU-001** — Asignación de código de barras a variantes de producto.
- **HU-002** — Escaneo de código de barras con cámara del celular durante la venta.

Se eliminó todo lo que no pertenece a estas dos historias (clientes/créditos, gastos, cuentas bancarias, cuadre de caja, préstamos externos, traslados, reportes, administración de usuarios/roles/configuración).

## Instalación

```bash
composer install
npm install
cp .env.example .env   # ya viene uno listo; ajusta credenciales de BD si es necesario
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Usuario de prueba: **admin** / **admin123**

## Flujo de prueba

1. Inicia sesión → redirige a Productos.
2. Crea un producto nuevo con 1-2 variantes (talla) sin escribir código de barras: se genera solo.
3. Ve a `/pos-movil` → "Venta Normal" → escanea (o pega manualmente) el código generado → agrega al carrito → registra la venta.
4. Verifica el ticket y que el stock del lote bajó correctamente.
