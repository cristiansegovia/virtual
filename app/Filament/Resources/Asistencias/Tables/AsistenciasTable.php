<?php

namespace App\Filament\Resources\Asistencias\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class AsistenciasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->getStateUsing(fn ($record) => $record->cliente->nombre . ' ' . $record->cliente->apellido)
                    ->searchable(query: function (\Illuminate\Database\Eloquent\Builder $query, string $search) {
                        $query->whereHas('cliente', function ($q) use ($search) {
                            $q->where('nombre', 'like', "%{$search}%")
                              ->orWhere('apellido', 'like', "%{$search}%");
                        });
                    })
                    ->color('primary')
                    ->action(
                        \Filament\Actions\Action::make('ver_cliente')
                            ->modalHeading('Información del Cliente')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Cerrar')
                            ->infolist([
                                \Filament\Schemas\Components\Section::make('Datos Principales')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('cliente.nombre')->label('Nombre'),
                                        \Filament\Infolists\Components\TextEntry::make('cliente.apellido')->label('Apellido'),
                                        \Filament\Infolists\Components\TextEntry::make('cliente.dni')->label('DNI'),
                                        \Filament\Infolists\Components\TextEntry::make('cliente.fecha_de_ingreso')
                                            ->label('Fecha de Ingreso')
                                            ->date('d/m/Y'),
                                    ])->columns(2),
                                \Filament\Schemas\Components\Section::make('Suscripciones')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('cliente.planes_list')
                                            ->label('Planes Inscritos')
                                            ->getStateUsing(fn ($record) => $record->cliente->planes->pluck('nombre')->implode(', ') ?: 'Ninguno'),
                                    ]),
                                \Filament\Schemas\Components\Section::make('Estado de Facturación')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('facturas_estado')
                                            ->label('Facturas Impagas / Pendientes')
                                            ->html()
                                            ->getStateUsing(function ($record) {
                                                $facturas = $record->cliente->facturas()->where('estado', '!=', 'pagada')->get();
                                                if ($facturas->isEmpty()) {
                                                    return '<span style="color: green; font-weight: bold;">Al día (Sin deudas)</span>';
                                                }
                                                return $facturas->map(function ($f) {
                                                    return "<a href=\"/admin/facturas/{$f->id}/edit\" target=\"_blank\" style=\"text-decoration: underline; color: #dc2626; font-weight: bold;\">Factura {$f->invoice_number} ({$f->estado}) - $ {$f->total}</a>";
                                                })->implode('<br>');
                                            })
                                    ]),
                            ])
                    ),
                \Filament\Tables\Columns\TextColumn::make('cliente.dni')
                    ->label('DNI')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('fecha_hora_ingreso')
                    ->label('Ingreso')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('fecha_hora_salida')
                    ->label('Salida')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('En curso'),
                \Filament\Tables\Columns\TextColumn::make('movimiento')
                    ->label('Movimiento')
                    ->getStateUsing(fn ($record) => $record->fecha_hora_salida ? 'Salida' : 'Ingreso')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ingreso' => 'success',
                        'Salida' => 'danger',
                    }),
                \Filament\Tables\Columns\TextColumn::make('duracion')
                    ->label('Duración (min)'),
                \Filament\Tables\Columns\TextColumn::make('contador_asistencias')
                    ->label('Asistencias (mes)'),
                \Filament\Tables\Columns\TextColumn::make('origen')
                    ->label('Origen'),
                \Filament\Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                // modal action moved to column
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
