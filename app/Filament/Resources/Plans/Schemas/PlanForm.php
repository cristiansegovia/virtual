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
                                'Acondicionamiento Fisico' => 'Acondicionamiento Fisico',
                                'Tecnica de Boxeo' => 'Tecnica de Boxeo',
                                'Stretching' => 'Stretching',
                                'Baile' => 'Baile',
                                'Otro' => 'Otro',
                            ])->required(),
                        \Filament\Forms\Components\TextInput::make('valor')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        \Filament\Forms\Components\Select::make('periodo')
                            ->options([
                                'Diario' => 'Diario',
                                'Mensual' => 'Mensual',
                                'Trimestral' => 'Trimestral',
                                'Semestral' => 'Semestral',
                                'Anual' => 'Anual',
                            ])->required(),
                        \Filament\Forms\Components\TextInput::make('contador')
                            ->numeric()
                            ->default(0)
                            ->label('Tope de Clases (0 = Ilimitado)')
                            ->helperText('Cantidad máxima de clases permitidas en el período.'),
                    ])->columns(2),
                    \Filament\Forms\Components\Textarea::make('descripcion')->columnSpanFull(),
                    \Filament\Forms\Components\Toggle::make('estado')->default(true)->required(),
                ]),
            ]);
    }
}
