<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bodega extends Model
{
    use HasFactory;

    protected $table = 'bodegas';

    protected $fillable = [
        'id_sucursal',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal');
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(LoteInventario::class, 'id_bodega');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
