<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $fillable = [
        'id_cliente',
        'fecha_hora_ingreso',
        'fecha_hora_salida',
        'origen',
        'contador_asistencias',
        'estado',
        'duracion',
        'clases_consumidas',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora_ingreso' => 'datetime',
            'fecha_hora_salida' => 'datetime',
            'estado' => 'boolean',
            'duracion' => 'integer',
            'contador_asistencias' => 'integer',
            'clases_consumidas' => 'integer',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }
}
