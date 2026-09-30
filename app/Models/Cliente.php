<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'foto_perfil',
        'nombre',
        'apellido',
        'dni',
        'domicilio',
        'telefono',
        'email',
        'estado',
        'fecha_de_ingreso',
        'fecha_de_egreso',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'fecha_de_ingreso' => 'date',
            'fecha_de_egreso' => 'date',
        ];
    }

    public function planes()
    {
        return $this->belongsToMany(Plan::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'id_cliente');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'cliente_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($cliente) {
            // Generar primera factura si tiene planes
            if ($cliente->planes->isNotEmpty()) {
                $totalInicial = (float) $cliente->planes->sum('valor');
                $factura = Factura::create([
                    'cliente_id' => $cliente->id,
                    'periodo' => 'mensual',
                    'estado' => 'vigente',
                    'detalle' => 'Factura inicial',
                    'fecha_emision' => now(),
                    'total' => $totalInicial,
                ]);

                $factura->planes()->attach($cliente->planes->pluck('id'));
                $factura->recalculateTotal();
            }
        });
    }
}
