<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\DetalleProducto;
use App\Models\LoteInventario;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['usuario' => 'admin'],
            [
                'nombre'      => 'Administrador',
                'password'    => Hash::make('admin123'),
                'activo'      => true,
                'id_sucursal' => 1,
                'id_bodega'   => 1,
            ]
        );
        $admin->assignRole('Admin');

        $categoria = Categoria::firstOrCreate(['nombre' => 'Calzado'], ['activo' => true]);

        $productos = [
            ['nombre' => 'Tenis Air Runner', 'precio_venta' => 180000, 'tallas' => ['38', '39', '40']],
            ['nombre' => 'Botas Urbanas', 'precio_venta' => 220000, 'tallas' => ['39', '41']],
            ['nombre' => 'Sandalias Playa', 'precio_venta' => 95000, 'tallas' => ['37', '38', '39']],
        ];

        foreach ($productos as $data) {
            $producto = Producto::create([
                'nombre'       => $data['nombre'],
                'id_categoria' => $categoria->id,
                'precio_venta' => $data['precio_venta'],
                'stock_minimo' => 5,
                'activo'       => true,
            ]);

            foreach ($data['tallas'] as $talla) {
                $detalle = DetalleProducto::create([
                    'id_producto'   => $producto->id,
                    'talla'         => $talla,
                    'codigo_barras' => DetalleProducto::generarCodigoBarras($producto->id),
                ]);

                LoteInventario::create([
                    'id_detalle_producto' => $detalle->id,
                    'id_bodega'           => 1,
                    'fecha_ingreso'       => now(),
                    'cantidad_disponible' => 10,
                    'costo_unitario'      => $data['precio_venta'] * 0.6,
                    'precio_venta'        => $data['precio_venta'],
                ]);
            }
        }
    }
}
