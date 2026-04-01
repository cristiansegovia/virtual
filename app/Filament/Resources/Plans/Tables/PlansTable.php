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
                \Filament\Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('categoria')->searchable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('valor')->money('USD')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('periodo')->searchable(),
                \Filament\Tables\Columns\IconColumn::make('estado')->boolean(),
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
