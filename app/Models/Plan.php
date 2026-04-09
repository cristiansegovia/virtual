<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'valor',
        'periodo',
        'categoria',
        'estado',
        'contador',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'double',
            'estado' => 'boolean',
            'contador' => 'integer',
        ];
    }

    public function clientes()
    {
        return $this->belongsToMany(Cliente::class);
    }

    public function facturas()
    {
        return $this->belongsToMany(Factura::class);
    }
}
