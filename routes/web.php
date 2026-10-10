<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

require __DIR__.'/auth.php';

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // CATEGORÍAS
    Route::middleware(['permission:productos.editar'])->group(function () {
        Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
        Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
        Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');
    });
    Route::middleware(['permission:productos.ver'])->get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');

    // PRODUCTOS (rutas estáticas antes del wildcard {producto})
    Route::middleware(['permission:productos.crear'])->group(function () {
        Route::get('/productos/crear', [ProductoController::class, 'create'])->name('productos.create');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
    });
    Route::middleware(['permission:productos.ver'])->group(function () {
        Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
        Route::get('/api/productos/buscar', [ProductoController::class, 'buscarPOS'])->name('productos.buscar-pos');
        Route::get('/productos/{producto}', [ProductoController::class, 'show'])->name('productos.show');
    });
    Route::middleware(['permission:productos.editar'])->group(function () {
        Route::get('/productos/{producto}/editar', [ProductoController::class, 'edit'])->name('productos.edit');
        Route::put('/productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
    });
    Route::middleware(['permission:productos.eliminar'])->delete('/productos/{producto}', [ProductoController::class, 'destroy'])->name('productos.destroy');

    // VENTAS / POS MÓVIL
    Route::middleware(['permission:ventas.crear'])->group(function () {
        Route::get('/ventas/nueva', [VentaController::class, 'create'])->name('ventas.create');
        Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    });
    Route::middleware(['permission:ventas.movil'])->group(function () {
        Route::get('/pos-movil', [VentaController::class, 'hubMovil'])->name('ventas.movil');
        Route::get('/pos-movil/venta', [VentaController::class, 'createMovil'])->name('ventas.movil.venta');
    });
    Route::middleware(['permission:ventas.ver'])->group(function () {
        Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
        Route::get('/ventas/{venta}', [VentaController::class, 'show'])->name('ventas.show');
        Route::get('/ventas/{venta}/ticket', [VentaController::class, 'ticket'])->name('ventas.ticket');
    });
    Route::middleware(['permission:ventas.anular'])->delete('/ventas/{venta}', [VentaController::class, 'destroy'])->name('ventas.destroy');
});
