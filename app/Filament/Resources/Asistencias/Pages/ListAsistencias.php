<?php

namespace App\Filament\Resources\Asistencias\Pages;

use App\Filament\Resources\Asistencias\AsistenciaResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Plan;
use Carbon\Carbon;

class ListAsistencias extends ListRecords
{
    protected static string $resource = AsistenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('registrar_ingreso')
                ->label('Registrar Ingreso')
                ->color('success')
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->form([
                    Select::make('id_cliente')
                        ->relationship('cliente', 'nombre')
                        ->getOptionLabelFromRecordUsing(fn ($record) => trim("{$record->nombre} {$record->apellido} ({$record->dni})"))
                        ->searchable(['nombre', 'apellido', 'dni'])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $set('id_plan', null);
                            if ($state) {
                                $cliente = Cliente::with('planes')->find($state);
                                if ($cliente && $cliente->planes->count() === 1) {
                                    $set('id_plan', $cliente->planes->first()->id);
                                }
                            }
                        })
                        ->label('Buscar Cliente'),

                    Select::make('id_plan')
                        ->label('Plan / Actividad')
                        ->options(function (callable $get) {
                            $clienteId = $get('id_cliente');
                            if (!$clienteId) {
                                return [];
                            }
                            $cliente = Cliente::with('planes')->find($clienteId);
                            if (!$cliente || $cliente->planes->isEmpty()) {
                                return [];
                            }
                            return $cliente->planes->mapWithKeys(function ($plan) {
                                $topeTexto = ($plan->contador === 0 || $plan->contador === null) ? 'Ilimitado' : "{$plan->contador} clases";
                                return [$plan->id => "{$plan->nombre} (Tope: {$topeTexto})"];
                            })->toArray();
                        })
                        ->placeholder(fn (callable $get) => $get('id_cliente') ? 'Seleccionar actividad / plan...' : 'Primero seleccione un cliente')
                        ->required()
                        ->live()
                        ->helperText(function (callable $get) {
                            $clienteId = $get('id_cliente');
                            $planId = $get('id_plan');
                            if (!$clienteId) {
                                return null;
                            }
                            if (!$planId) {
                                $cliente = Cliente::with('planes')->find($clienteId);
                                if (!$cliente || $cliente->planes->isEmpty()) {
                                    return '⚠️ El cliente no tiene ningún plan asignado.';
                                }
                                return 'Seleccione la actividad a la que ingresa el cliente.';
                            }

                            $plan = Plan::find($planId);
                            if (!$plan) {
                                return null;
                            }

                            $cliente = Cliente::find($clienteId);
                            $facturas = $cliente?->facturas()
                                ->whereIn('estado', ['vigente', 'pagada'])
                                ->orderBy('fecha_emision', 'desc')
                                ->get();

                            $factura = $facturas?->first(fn($f) => $f->coversDate(now()) && ($f->planes->isEmpty() || $f->planes->contains('id', $planId)));

                            if (!$factura) {
                                return "⚠️ Sin factura vigente o pagada que cubra este plan en la fecha actual.";
                            }

                            if ($plan->contador === 0 || $plan->contador === null) {
                                return "✅ Plan ilimitado - Factura #{$factura->invoice_number} ({$factura->estado})";
                            }

                            $inicio = ($factura->fecha_emision ?? $factura->created_at)?->startOfDay() ?? now()->startOfMonth();
                            $consumidas = Asistencia::where('id_cliente', $clienteId)
                                ->where('id_plan', $planId)
                                ->where('created_at', '>=', $inicio)
                                ->sum('clases_consumidas');
                            $disponibles = max(0, $plan->contador - $consumidas);

                            return "Disponibles en este período: {$disponibles} de {$plan->contador} clases (Factura #{$factura->invoice_number}).";
                        }),

                    TextInput::make('cantidad_clases')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required()
                        ->label('Cantidad de Clases')
                ])
                ->action(function (array $data) {
                    $cliente = Cliente::with('planes')->find($data['id_cliente']);
                    $plan = Plan::find($data['id_plan']);
                    $hoy = now();

                    if (!$cliente || !$plan) {
                        return;
                    }

                    // Verificar si el cliente tiene asignado este plan
                    if (!$cliente->planes->contains('id', $plan->id)) {
                        $this->js("
                            const showAlert = () => {
                                Swal.fire({
                                    title: 'Acceso Denegado',
                                    text: 'El cliente no tiene asignado el plan seleccionado ({$plan->nombre}).',
                                    icon: 'error',
                                    confirmButtonText: 'Aceptar',
                                    confirmButtonColor: '#10b981',
                                    allowOutsideClick: false
                                });
                            };
                            if (typeof Swal === 'undefined') {
                                let script = document.createElement('script');
                                script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                                script.onload = showAlert;
                                document.head.appendChild(script);
                            } else {
                                showAlert();
                            }
                        ");
                        return;
                    }

                    // Verificar si ya tiene un ingreso abierto en ESTA actividad/plan hoy
                    $asistenciaAbierta = Asistencia::where('id_cliente', $cliente->id)
                        ->where('id_plan', $plan->id)
                        ->whereNull('fecha_hora_salida')
                        ->whereDate('fecha_hora_ingreso', $hoy->toDateString())
                        ->exists();

                    if ($asistenciaAbierta) {
                        $this->js("
                            const showAlert = () => {
                                Swal.fire({
                                    title: 'Error al registrar ingreso',
                                    text: 'El cliente ya se encuentra dentro de la actividad ({$plan->nombre}) con un ingreso abierto.',
                                    icon: 'error',
                                    confirmButtonText: 'Aceptar',
                                    confirmButtonColor: '#10b981',
                                    allowOutsideClick: false
                                });
                            };
                            if (typeof Swal === 'undefined') {
                                let script = document.createElement('script');
                                script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                                script.onload = showAlert;
                                document.head.appendChild(script);
                            } else {
                                showAlert();
                            }
                        ");
                        return;
                    }

                    // Validar factura vigente o pagada que cubra la fecha y el plan
                    $tieneFacturaValida = false;
                    $facturaValida = null;
                    $ultimaEmisionValida = null;

                    $facturas = $cliente->facturas()->orderBy('fecha_emision', 'desc')->get();

                    foreach ($facturas as $factura) {
                        if (!in_array($factura->estado, ['vigente', 'pagada'])) {
                            continue;
                        }

                        if ($factura->coversDate($hoy)) {
                            $planesEnFactura = $factura->planes;
                            if ($planesEnFactura->isEmpty() || $planesEnFactura->contains('id', $plan->id)) {
                                $tieneFacturaValida = true;
                                $facturaValida = $factura;
                                $emision = clone ($factura->fecha_emision ?? $factura->created_at);
                                $ultimaEmisionValida = clone $emision->startOfDay();
                                break;
                            }
                        }
                    }

                    if (!$tieneFacturaValida) {
                        $this->js("
                            const showAlert = () => {
                                Swal.fire({
                                    title: 'Deuda o Factura Inválida',
                                    text: 'El cliente no posee una factura vigente o pagada que cubra la actividad ({$plan->nombre}) en la fecha actual.',
                                    icon: 'error',
                                    confirmButtonText: 'Aceptar',
                                    confirmButtonColor: '#10b981',
                                    allowOutsideClick: false
                                });
                            };
                            if (typeof Swal === 'undefined') {
                                let script = document.createElement('script');
                                script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                                script.onload = showAlert;
                                document.head.appendChild(script);
                            } else {
                                showAlert();
                            }
                        ");
                        return;
                    }

                    // Cálculo de contadores independientes por plan
                    $esIlimitado = ($plan->contador === 0 || $plan->contador === null);
                    $limiteTope = (int) $plan->contador;
                    $inicio_periodo = $ultimaEmisionValida ?? $hoy->copy()->startOfMonth();

                    $visitasEstePeriodo = Asistencia::where('id_cliente', $cliente->id)
                        ->where('id_plan', $plan->id)
                        ->where('created_at', '>=', $inicio_periodo)
                        ->sum('clases_consumidas');

                    $restantes = 0;
                    $clases_pedidas = (int) $data['cantidad_clases'];

                    if (!$esIlimitado) {
                        $restantesActuales = $limiteTope - $visitasEstePeriodo;

                        if ($restantesActuales < $clases_pedidas) {
                            $this->js("
                                const showAlert = () => {
                                    Swal.fire({
                                        title: 'Límite Insuficiente',
                                        text: 'El cliente solicita {$clases_pedidas} clases, pero solo dispone de {$restantesActuales} asistencias en su tope para la actividad \"{$plan->nombre}\" en este período.',
                                        icon: 'warning',
                                        confirmButtonText: 'Aceptar',
                                        confirmButtonColor: '#10b981',
                                        allowOutsideClick: false
                                    });
                                };
                                if (typeof Swal === 'undefined') {
                                    let script = document.createElement('script');
                                    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                                    script.onload = showAlert;
                                    document.head.appendChild(script);
                                } else {
                                    showAlert();
                                }
                            ");
                            return;
                        }

                        $restantes = $restantesActuales - $clases_pedidas;
                    }

                    Asistencia::create([
                        'id_cliente' => $cliente->id,
                        'id_plan' => $plan->id,
                        'id_factura' => $facturaValida?->id,
                        'fecha_hora_ingreso' => $hoy,
                        'fecha_hora_salida' => null,
                        'origen' => 'admin',
                        'contador_asistencias' => $restantes,
                        'clases_consumidas' => $clases_pedidas,
                        'estado' => true,
                        'duracion' => null,
                        'created_at' => $hoy,
                    ]);

                    Notification::make()
                        ->title('Ingreso registrado con éxito')
                        ->body("Actividad: {$plan->nombre}")
                        ->success()
                        ->send();
                }),

            Action::make('registrar_salida')
                ->label('Registrar Salida')
                ->color('danger')
                ->icon('heroicon-o-arrow-left-start-on-rectangle')
                ->form([
                    Select::make('id_cliente')
                        ->relationship('cliente', 'nombre')
                        ->getOptionLabelFromRecordUsing(fn ($record) => trim("{$record->nombre} {$record->apellido} ({$record->dni})"))
                        ->searchable(['nombre', 'apellido', 'dni'])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $set('asistencia_id', null);
                            if ($state) {
                                $abiertas = Asistencia::where('id_cliente', $state)
                                    ->whereNull('fecha_hora_salida')
                                    ->whereDate('fecha_hora_ingreso', now()->toDateString())
                                    ->get();
                                if ($abiertas->count() === 1) {
                                    $set('asistencia_id', $abiertas->first()->id);
                                }
                            }
                        })
                        ->label('Buscar Cliente'),

                    Select::make('asistencia_id')
                        ->label('Ingreso Abierto / Actividad')
                        ->options(function (callable $get) {
                            $clienteId = $get('id_cliente');
                            if (!$clienteId) {
                                return [];
                            }

                            $abiertas = Asistencia::with('plan')
                                ->where('id_cliente', $clienteId)
                                ->whereNull('fecha_hora_salida')
                                ->whereDate('fecha_hora_ingreso', now()->toDateString())
                                ->orderBy('fecha_hora_ingreso', 'desc')
                                ->get();

                            return $abiertas->mapWithKeys(function ($asistencia) {
                                $actividad = $asistencia->plan?->nombre ?? 'Sin actividad asignada';
                                $hora = $asistencia->fecha_hora_ingreso ? $asistencia->fecha_hora_ingreso->format('H:i') : '';
                                return [$asistencia->id => "{$actividad} (Ingreso: {$hora} hs)"];
                            })->toArray();
                        })
                        ->visible(fn(callable $get) => (bool) $get('id_cliente'))
                        ->required(fn(callable $get) => (bool) $get('id_cliente'))
                        ->placeholder('Seleccione el ingreso a cerrar...'),
                ])
                ->action(function (array $data) {
                    $hoy = now();
                    $asistenciaId = $data['asistencia_id'] ?? null;

                    $asistencia = null;
                    if ($asistenciaId) {
                        $asistencia = Asistencia::with('plan')->find($asistenciaId);
                    } else {
                        $asistencia = Asistencia::with('plan')
                            ->where('id_cliente', $data['id_cliente'])
                            ->whereNull('fecha_hora_salida')
                            ->whereDate('fecha_hora_ingreso', $hoy->toDateString())
                            ->latest('fecha_hora_ingreso')
                            ->first();
                    }

                    if (!$asistencia) {
                        Notification::make()
                            ->title('Error al registrar salida')
                            ->body('El cliente seleccionado no registra un ingreso abierto en el día de la fecha.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $duracion = $asistencia->fecha_hora_ingreso ? $asistencia->fecha_hora_ingreso->diffInMinutes($hoy) : 0;

                    $asistencia->update([
                        'fecha_hora_salida' => $hoy,
                        'duracion' => $duracion,
                    ]);

                    $planNombre = $asistencia->plan?->nombre ? " para {$asistencia->plan->nombre}" : "";
                    Notification::make()
                        ->title('Salida registrada con éxito')
                        ->body("Se registró la salida{$planNombre}. Duración: {$duracion} min.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
