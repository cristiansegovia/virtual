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
                $factura = Factura::create([
                    'cliente_id' => $cliente->id,
                    'periodo' => 'mensual', // o determinar basado en planes
                    'estado' => 'vigente',
                    'detalle' => 'Factura inicial',
                    'fecha_emision' => now(),
                ]);

                $factura->planes()->attach($cliente->planes->pluck('id'));
                // El saving event calculará fecha_vencimiento y total
            }
        });
    }
}
