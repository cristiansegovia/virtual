<?php

namespace App\Filament\Resources\Facturas\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FacturasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->sortable(),
                TextColumn::make('periodo')
                    ->sortable(),
                TextColumn::make('estado')
                    ->sortable(),
                TextColumn::make('fecha_emision')
                    ->date()
                    ->sortable(),
                TextColumn::make('fecha_vencimiento')
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->money('USD')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('marcar_pagada')
                    ->label('Pagada')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (\App\Models\Factura $record) => $record->update(['estado' => 'pagada']))
                    ->visible(fn (\App\Models\Factura $record) => in_array($record->estado, ['vigente', 'vencida'])),
                Action::make('marcar_cancelada')
                    ->label('Cancelada')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn (\App\Models\Factura $record) => $record->update(['estado' => 'cancelada']))
                    ->visible(fn (\App\Models\Factura $record) => in_array($record->estado, ['vigente', 'vencida'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
