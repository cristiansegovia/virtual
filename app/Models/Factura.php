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

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'id_factura');
    }

    public function getFechaFinAttribute(): \Carbon\Carbon
    {
        $emision = $this->fecha_emision ? clone $this->fecha_emision : ($this->created_at ? clone $this->created_at : now());
        $emision = \Carbon\Carbon::parse($emision)->startOfDay();
        $fin = clone $emision;

        switch (strtolower($this->periodo ?? 'mensual')) {
            case 'diario':
                $fin->addDay();
                break;
            case 'mensual':
                $fin->addMonth();
                break;
            case 'trimestral':
                $fin->addMonths(3);
                break;
            case 'semestral':
                $fin->addMonths(6);
                break;
            case 'anual':
                $fin->addYear();
                break;
            case 'pase libre':
                $fin->addMonth();
                break;
            default:
                $fin->addMonth();
                break;
        }

        return $fin->endOfDay();
    }

    public function coversDate($date): bool
    {
        $target = \Carbon\Carbon::parse($date);
        $emision = $this->fecha_emision ? clone $this->fecha_emision : ($this->created_at ? clone $this->created_at : now());
        $inicio = \Carbon\Carbon::parse($emision)->startOfDay();
        $fin = $this->fecha_fin;

        return $target->betweenIncluded($inicio, $fin);
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
