<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $fillable = [
        'cliente_id',
        'periodo',
        'estado',
        'detalle',
        'fecha_emision',
        'fecha_vencimiento',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
            'total' => 'decimal:2',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function planes()
    {
        return $this->belongsToMany(Plan::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($factura) {
            // Calcular fecha_vencimiento: 7 días después de fecha_emision
            if ($factura->fecha_emision) {
                $factura->fecha_vencimiento = $factura->fecha_emision->addDays(7);
            }

            // Calcular total como suma de valores de planes
            if ($factura->planes->isNotEmpty()) {
                $factura->total = $factura->planes->sum('valor');
            }
        });
    }
}
