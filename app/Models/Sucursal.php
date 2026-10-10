<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function bodegas(): HasMany
    {
        return $this->hasMany(Bodega::class, 'id_sucursal');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'id_sucursal');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'id_sucursal');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
