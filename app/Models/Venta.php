<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'id_usuario',
        'id_sucursal',
        'metodo_pago',
        'total',
        'observaciones',
        'anulada',
        'motivo_anulacion',
    ];

    protected $casts = [
        'anulada' => 'boolean',
        'total' => 'decimal:2',
    ];

    const METODO_EFECTIVO = 'Efectivo';

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal');
    }

    public function historialVentas(): HasMany
    {
        return $this->hasMany(HistorialVenta::class, 'id_venta');
    }

    /**
     * Calcula el total de la venta sumando los ítems
     */
    public function calcularTotal(): float
    {
        return $this->historialVentas->sum(fn($item) => $item->cantidad * $item->precio_venta);
    }

    /**
     * Scope para excluir ventas anuladas (usar en todos los conteos/reportes)
     */
    public function scopeActivas($query)
    {
        return $query->where('anulada', false);
    }

    /**
     * Scope para ventas del día (solo activas)
     */
    public function scopeDeHoy($query)
    {
        return $query->activas()->whereDate('created_at', today());
    }

    /**
     * Scope filtrar por período
     */
    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('created_at', [$inicio, $fin]);
    }
}
