<?php

namespace App\Filament\Resources\Clientes\Schemas;

use Filament\Schemas\Schema;

class ClienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Datos Personales')->schema([
                    \Filament\Schemas\Components\Group::make([
                        \Filament\Forms\Components\FileUpload::make('foto_perfil')
                            ->image()
                            ->avatar()
                            ->columnSpanFull()
                            ->alignCenter(),
                        \Filament\Forms\Components\TextInput::make('nombre')->required(),
                        \Filament\Forms\Components\TextInput::make('apellido')->required(),
                        \Filament\Forms\Components\TextInput::make('dni')->required()->unique(ignoreRecord: true),
                        \Filament\Forms\Components\TextInput::make('email')->email()->unique(ignoreRecord: true),
                        \Filament\Forms\Components\TextInput::make('telefono'),
                        \Filament\Forms\Components\TextInput::make('domicilio'),
                    ])->columns(2),
                ]),
                \Filament\Schemas\Components\Section::make('Estado e Inscripción')->schema([
                    \Filament\Schemas\Components\Group::make([
                        \Filament\Forms\Components\Toggle::make('estado')->default(true),
                        \Filament\Forms\Components\DatePicker::make('fecha_de_ingreso')->native(false),
                        \Filament\Forms\Components\DatePicker::make('fecha_de_egreso')->native(false),
                    ])->columns(3),
                ]),
                \Filament\Schemas\Components\Section::make('Planes Asociados')->schema([
                    \Filament\Forms\Components\Select::make('planes')
                        ->relationship('planes', 'nombre')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->label('Seleccionar Planes'),
                ]),
            ]);
    }
}
