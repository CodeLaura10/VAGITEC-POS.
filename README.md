# VISOFTEC POS

Sistema de punto de venta (POS) desarrollado en Laravel que permite asignar códigos de barras a las variantes de producto y registrar ventas escaneando esos códigos con la cámara del celular.

## Historias de usuario

### HU-001 — Asignación de código de barras a variantes de producto

**Como** administrador del inventario,
**quiero** que cada variante de un producto (por ejemplo, cada talla) tenga un código de barras único,
**para** identificarla rápidamente al momento de vender.

**Criterios de aceptación**

- Al crear o editar un producto se pueden registrar una o varias variantes.
- Si no se escribe un código de barras, el sistema lo genera automáticamente.
- El código de barras es único: no se puede repetir entre variantes.
- El código se puede consultar en el detalle del producto.

### HU-002 — Escaneo de código de barras con la cámara del celular durante la venta

**Como** vendedor,
**quiero** escanear el código de barras de un producto con la cámara de mi celular,
**para** agregarlo al carrito sin tener que buscarlo a mano.

**Criterios de aceptación**

- Desde `/pos-movil` → "Venta Normal" se abre el escáner con la cámara del dispositivo.
- Al leer un código válido, la variante correspondiente se agrega al carrito.
- El código también se puede escribir o pegar manualmente.
- Al registrar la venta se descuenta el stock del lote de inventario y se genera el ticket.

## Tecnologías usadas

**Backend**

- PHP 8.2
- Laravel 11 (con Laravel Breeze para la autenticación)
- Spatie Laravel Permission (roles y permisos)
- MySQL

**Frontend**

- Blade
- Tailwind CSS 3
- Alpine.js
- html5-qrcode (lectura de códigos con la cámara)
- Vite 5

## Requisitos

- PHP 8.2 o superior
- Composer
- Node.js y npm
- MySQL (por ejemplo, con XAMPP)

## Instrucciones para ejecutarlo

1. Instala las dependencias de PHP y de JavaScript:

   ```bash
   composer install
   npm install
   ```

2. Crea el archivo de entorno y genera la clave de la aplicación:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Crea en MySQL una base de datos llamada `visoftec_hu001_hu002` y, si hace falta, ajusta en `.env` las variables `DB_HOST`, `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD`.

4. Ejecuta las migraciones y carga los datos de prueba:

   ```bash
   php artisan migrate --seed
   ```

5. Compila los recursos del frontend:

   ```bash
   npm run build
   ```

   Si vas a modificar estilos o scripts, usa `npm run dev` en su lugar.

6. Levanta el servidor:

   ```bash
   php artisan serve
   ```

7. Abre `http://127.0.0.1:8000` en el navegador e inicia sesión con el usuario de prueba:

   - **Usuario:** `admin`
   - **Contraseña:** `admin123`

> **Nota:** el navegador solo permite usar la cámara en `localhost` o bajo HTTPS. Para probar el escáner desde un celular necesitas exponer la aplicación por HTTPS (por ejemplo, con un túnel como ngrok).

## Flujo de prueba

1. Inicia sesión; el sistema te lleva a Productos.
2. Crea un producto con una o dos variantes (talla) sin escribir código de barras: el código se genera automáticamente (HU-001).
3. Ve a `/pos-movil` → "Venta Normal", escanea (o pega) el código generado, agrégalo al carrito y registra la venta (HU-002).
4. Revisa el ticket y confirma que el stock del lote bajó.
