<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Detalles del Plan')->schema([
                    \Filament\Schemas\Components\Group::make([
                        \Filament\Forms\Components\TextInput::make('nombre')->required(),
                        \Filament\Forms\Components\Select::make('categoria')
                            ->options([
                                'Musculación' => 'Musculación',
                                'Crossfit' => 'Crossfit',
                                'Funcional' => 'Funcional',
                                'Yoga' => 'Yoga',
                                'Pileta' => 'Pileta',
                                'Otro' => 'Otro',
                            ])->required(),
                        \Filament\Forms\Components\TextInput::make('valor')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        \Filament\Forms\Components\Select::make('periodo')
                            ->options([
                                'Mensual' => 'Mensual',
                                'Trimestral' => 'Trimestral',
                                'Semestral' => 'Semestral',
                                'Anual' => 'Anual',
                                'Pase Libre' => 'Pase Libre',
                            ])->required(),
                    ])->columns(2),
                    \Filament\Forms\Components\Textarea::make('descripcion')->columnSpanFull(),
                    \Filament\Forms\Components\Toggle::make('estado')->default(true)->required(),
                ]),
            ]);
    }
}
