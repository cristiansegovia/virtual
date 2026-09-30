<?php

namespace App\Filament\Resources\Asistencias\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AsistenciasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->getStateUsing(fn($record) => $record->cliente ? ($record->cliente->nombre . ' ' . $record->cliente->apellido) : 'Cliente eliminado')
                    ->searchable(query: function (\Illuminate\Database\Eloquent\Builder $query, string $search) {
                        $query->whereHas('cliente', function ($q) use ($search) {
                            $q->where('nombre', 'like', "%{$search}%")
                                ->orWhere('apellido', 'like', "%{$search}%");
                        });
                    })
                    ->color('primary')
                    ->action(
                        \Filament\Actions\Action::make('ver_cliente')
                            ->modalHeading('Información del Ingreso y Cliente')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Cerrar')
                            ->infolist([
                                \Filament\Schemas\Components\Section::make('Detalles de la Asistencia')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('plan.nombre')
                                            ->label('Actividad / Plan')
                                            ->placeholder('Sin plan asociado')
                                            ->badge()
                                            ->color('info'),
                                        \Filament\Infolists\Components\TextEntry::make('clases_consumidas')
                                            ->label('Clases Consumidas en este Ingreso'),
                                        \Filament\Infolists\Components\TextEntry::make('contador_asistencias')
                                            ->label('Clases Restantes del Plan')
                                            ->getStateUsing(fn($record) => ($record->plan && ($record->plan->contador === 0 || $record->plan->contador === null)) ? 'Ilimitado' : $record->contador_asistencias),
                                        \Filament\Infolists\Components\TextEntry::make('fecha_hora_ingreso')
                                            ->label('Hora de Ingreso')
                                            ->dateTime('d/m/Y H:i'),
                                        \Filament\Infolists\Components\TextEntry::make('fecha_hora_salida')
                                            ->label('Hora de Salida')
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('Ingreso aún abierto'),
                                        \Filament\Infolists\Components\TextEntry::make('duracion')
                                            ->label('Duración')
                                            ->getStateUsing(fn($record) => $record->duracion ? "{$record->duracion} minutos" : '-'),
                                    ])->columns(3),
                                \Filament\Schemas\Components\Section::make('Datos del Cliente')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('cliente.nombre')->label('Nombre'),
                                        \Filament\Infolists\Components\TextEntry::make('cliente.apellido')->label('Apellido'),
                                        \Filament\Infolists\Components\TextEntry::make('cliente.dni')->label('DNI'),
                                        \Filament\Infolists\Components\TextEntry::make('cliente.fecha_de_ingreso')
                                            ->label('Fecha de Ingreso')
                                            ->date('d/m/Y'),
                                    ])->columns(2),
                                \Filament\Schemas\Components\Section::make('Suscripciones Activas')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('cliente.planes_list')
                                            ->label('Planes Asignados al Cliente')
                                            ->getStateUsing(fn($record) => $record->cliente?->planes->pluck('nombre')->implode(', ') ?: 'Ninguno'),
                                    ]),
                                \Filament\Schemas\Components\Section::make('Facturación')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('factura_actual_asistencia')
                                            ->label('Factura Habilitante del Ingreso')
                                            ->html()
                                            ->getStateUsing(function ($record) {
                                                $f = $record->factura;
                                                if (!$f) {
                                                    return '<span style="color: #6b7280; font-style: italic;">Sin factura vinculada</span>';
                                                }
                                                $color = match ($f->estado) {
                                                    'pagada' => '#16a34a',
                                                    'vigente' => '#2563eb',
                                                    'vencida' => '#dc2626',
                                                    'cancelada' => '#6b7280',
                                                    default => '#374151',
                                                };
                                                return "<a href=\"/admin/facturas/{$f->id}/edit\" target=\"_blank\" style=\"text-decoration: underline; color: {$color}; font-weight: bold;\">Factura #{$f->invoice_number} ({$f->estado}) - $ {$f->total}</a>";
                                            }),
                                        \Filament\Infolists\Components\TextEntry::make('facturas_estado')
                                            ->label('Estado General del Cliente (Deudas Actuales)')
                                            ->html()
                                            ->getStateUsing(function ($record) {
                                                if (!$record->cliente) {
                                                    return '-';
                                                }
                                                $facturas = $record->cliente->facturas()->where('estado', '!=', 'pagada')->get();
                                                if ($facturas->isEmpty()) {
                                                    return '<span style="color: green; font-weight: bold;">Al día (Sin deudas)</span>';
                                                }
                                                return $facturas->map(function ($f) {
                                                    return "<a href=\"/admin/facturas/{$f->id}/edit\" target=\"_blank\" style=\"text-decoration: underline; color: #dc2626; font-weight: bold;\">Factura #{$f->invoice_number} ({$f->estado}) - $ {$f->total}</a>";
                                                })->implode('<br>');
                                            })
                                    ])->columns(1),
                            ])
                    ),
                \Filament\Tables\Columns\TextColumn::make('plan.nombre')
                    ->label('Actividad / Plan')
                    ->badge()
                    ->color('info')
                    ->placeholder('Sin especificar')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('cliente.dni')
                    ->label('DNI')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('fecha_hora_ingreso')
                    ->label('Ingreso')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('movimiento')
                    ->label('Movimiento')
                    ->getStateUsing(fn($record) => $record->fecha_hora_salida ? 'Salida' : 'Ingreso')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Ingreso' => 'success',
                        'Salida' => 'danger',
                    }),
                \Filament\Tables\Columns\TextColumn::make('clases_consumidas')
                    ->label('Clases')
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('contador_asistencias')
                    ->label('Restantes')
                    ->alignCenter()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->plan && ($record->plan->contador === 0 || $record->plan->contador === null)) {
                            return 'Ilimitado';
                        }
                        return $state;
                    }),
                \Filament\Tables\Columns\TextColumn::make('estado_facturacion')
                    ->label('Estado de Facturación')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $factura = $record->factura;
                        if (!$factura) {
                            return '<span style="color: #6b7280; font-style: italic;">Sin factura asociada</span>';
                        }
                        
                        $color = match ($factura->estado) {
                            'pagada' => '#16a34a',
                            'vigente' => '#2563eb',
                            'vencida' => '#dc2626',
                            'cancelada' => '#6b7280',
                            default => '#374151',
                        };

                        return "<a href=\"/admin/facturas/{$factura->id}/edit\" target=\"_blank\" style=\"text-decoration: underline; color: {$color}; font-weight: 600;\">Factura {$factura->invoice_number} ({$factura->estado}) - $ {$factura->total}</a>";
                    }),
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
            ->defaultSort('asistencias.updated_at', 'desc')
            ->filters([
                SelectFilter::make('id_plan')
                    ->relationship('plan', 'nombre')
                    ->label('Filtrar por Plan / Actividad')
                    ->searchable()
                    ->preload(),
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
