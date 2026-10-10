<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_detalle_producto')->constrained('detalle_productos')->restrictOnDelete();
            $table->foreignId('id_bodega')->nullable()->constrained('bodegas')->nullOnDelete();
            $table->timestamp('fecha_ingreso')->useCurrent();
            $table->integer('cantidad_disponible')->default(0);
            $table->decimal('costo_unitario', 18, 2);
            $table->decimal('precio_venta', 18, 2)->nullable();
            $table->timestamps();

            $table->index('id_detalle_producto', 'idx_lotes_detalle');
            $table->index('id_bodega');
            $table->index('fecha_ingreso', 'idx_lotes_fecha');
            // Índice compuesto para consultas FIFO
            $table->index(['id_detalle_producto', 'fecha_ingreso', 'cantidad_disponible'], 'idx_lotes_fifo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes_inventario');
    }
};
