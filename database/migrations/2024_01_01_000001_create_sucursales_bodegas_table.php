<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('bodegas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_sucursal')->constrained('sucursales')->restrictOnDelete();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('id_sucursal');
            $table->index('activo');
        });

        DB::table('sucursales')->insert([
            'id'         => 1,
            'nombre'     => 'Principal',
            'activo'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('bodegas')->insert([
            'id'          => 1,
            'id_sucursal' => 1,
            'nombre'      => 'Principal',
            'activo'      => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bodegas');
        Schema::dropIfExists('sucursales');
    }
};
