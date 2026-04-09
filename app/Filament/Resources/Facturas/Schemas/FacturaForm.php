<?php

namespace App\Filament\Resources\Facturas\Schemas;

use App\Models\Plan;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FacturaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cliente_id')
                    ->relationship('cliente', 'nombre')
                    ->getOptionLabelFromRecordUsing(fn($record) => trim("{$record->nombre} {$record->apellido} ({$record->dni})"))
                    ->live()
                    ->searchable(['nombre', 'apellido', 'dni'])
                    ->required(),
                Select::make('periodo')
                    ->options([
                        'diario' => 'Diario',
                        'mensual' => 'Mensual',
                        'trimestral' => 'Trimestral',
                        'semestral' => 'Semestral',
                        'anual' => 'Anual',
                    ])
                    ->required(),
                CheckboxList::make('planes')
                    ->relationship('planes', 'nombre')
                    ->options(fn($get) => $get('cliente_id') ? Plan::whereHas('clientes', fn($q) => $q->where('clientes.id', $get('cliente_id')))->pluck('nombre', 'id') : [])
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn($state, callable $set) => $set('total', Plan::whereIn('id', $state ?? [])->sum('valor')))
                    ->required(),
                DatePicker::make('fecha_emision')
                    ->default(now())
                    ->required(),
                DatePicker::make('fecha_vencimiento')
                    ->disabled(),
                TextInput::make('total')
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
                Textarea::make('detalle')
                    ->nullable(),
            ]);
    }
}
