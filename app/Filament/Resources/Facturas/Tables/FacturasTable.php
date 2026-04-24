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
                    ->formatStateUsing(fn ($record) => trim("{$record->cliente->nombre} {$record->cliente->apellido}"))
                    ->searchable(['nombre', 'apellido'])
                    ->sortable(),
                TextColumn::make('periodo')
                    ->label('Período')
                    ->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->sortable(),
                TextColumn::make('fecha_emision')
                    ->label('Fecha de Emisión')
                    ->date()
                    ->sortable(),
                TextColumn::make('fecha_vencimiento')
                    ->label('Fecha de Vencimiento')
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
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
