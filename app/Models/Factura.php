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

    public function getInvoiceNumberAttribute(): string
    {
        return str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    protected static $updatingTotal = false;

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($factura) {
            // Calcular fecha_vencimiento: 7 días después de fecha_emision
            if ($factura->fecha_emision) {
                $factura->fecha_vencimiento = $factura->fecha_emision->addDays(7);
            }
        });

        static::saved(function ($factura) {
            if (!self::$updatingTotal) {
                self::$updatingTotal = true;
                $calculated = $factura->planes->sum('valor');
                if ($factura->total != $calculated) {
                    $factura->total = $calculated;
                    $factura->save();
                }
                self::$updatingTotal = false;
            }
        });
    }
}
