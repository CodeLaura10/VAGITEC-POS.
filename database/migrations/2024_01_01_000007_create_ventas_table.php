<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->foreignId('id_sucursal')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->string('metodo_pago', 20)->default('Efectivo');
            $table->decimal('total', 18, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->boolean('anulada')->default(false);
            $table->string('motivo_anulacion')->nullable();
            $table->timestamps();

            $table->index('id_usuario');
            $table->index('id_sucursal');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
