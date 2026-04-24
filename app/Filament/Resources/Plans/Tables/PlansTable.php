<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('categoria')->label('Categoría')->searchable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('valor')->label('Valor')->money('USD')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('periodo')->label('Período')->searchable(),
                \Filament\Tables\Columns\IconColumn::make('estado')->label('Estado')->boolean(),
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
