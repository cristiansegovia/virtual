<?php

namespace App\Filament\Resources\Asistencias\Schemas;

use Filament\Schemas\Schema;

class AsistenciaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('id_cliente')
                    ->relationship('cliente', 'nombre')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nombre} {$record->apellido} ({$record->dni})")
                    ->searchable()
                    ->required()
                    ->label('Cliente'),
                \Filament\Forms\Components\DateTimePicker::make('fecha_hora_ingreso')
                    ->required()
                    ->label('Fecha y Hora de Ingreso')
                    ->default(now()),
                \Filament\Forms\Components\DateTimePicker::make('fecha_hora_salida')
                    ->label('Fecha y Hora de Salida'),
                \Filament\Forms\Components\TextInput::make('origen')
                    ->required()
                    ->default('admin')
                    ->label('Origen'),
                \Filament\Forms\Components\TextInput::make('contador_asistencias')
                    ->numeric()
                    ->default(0)
                    ->label('Asistencias del Mes'),
                \Filament\Forms\Components\TextInput::make('duracion')
                    ->numeric()
                    ->label('Duración (min)'),
                \Filament\Forms\Components\Toggle::make('estado')
                    ->default(true)
                    ->label('Activo')
            ]);
    }
}
