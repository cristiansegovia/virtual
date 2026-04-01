<?php

namespace App\Filament\Resources\Clientes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class ClientesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\ImageColumn::make('foto_perfil')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->nombre . ' ' . $record->apellido) . '&color=FFFFFF&background=10b981'),
                \Filament\Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('apellido')->searchable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('dni')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('telefono')->searchable(),
                \Filament\Tables\Columns\IconColumn::make('estado')->boolean(),
                \Filament\Tables\Columns\TextColumn::make('fecha_de_ingreso')->date()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
