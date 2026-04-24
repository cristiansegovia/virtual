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
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre')
                    ->getOptionLabelFromRecordUsing(fn($record) => trim("{$record->nombre} {$record->apellido} ({$record->dni})"))
                    ->live()
                    ->searchable(['nombre', 'apellido', 'dni'])
                    ->default(request()->query('cliente_id'))
                    ->required(),
                Select::make('periodo')
                    ->label('Período')
                    ->options([
                        'diario' => 'Diario',
                        'mensual' => 'Mensual',
                        'trimestral' => 'Trimestral',
                        'semestral' => 'Semestral',
                        'anual' => 'Anual',
                    ])
                    ->required(),
                CheckboxList::make('planes')
                    ->label('Planes')
                    ->relationship('planes', 'nombre')
                    ->options(fn($get) => $get('cliente_id') ? Plan::whereHas('clientes', fn($q) => $q->where('clientes.id', $get('cliente_id')))->pluck('nombre', 'id') : [])
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn($state, callable $set) => $set('total', Plan::whereIn('id', $state ?? [])->sum('valor')))
                    ->required(),
                DatePicker::make('fecha_emision')
                    ->label('Fecha de Emisión')
                    ->default(now())
                    ->required(),
                DatePicker::make('fecha_vencimiento')
                    ->label('Fecha de Vencimiento')
                    ->disabled(),
                TextInput::make('total')
                    ->label('Total')
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
                Textarea::make('detalle')
                    ->label('Detalle')
                    ->nullable(),
            ]);
    }
}
