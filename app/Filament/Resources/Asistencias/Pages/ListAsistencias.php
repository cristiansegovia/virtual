<?php

namespace App\Filament\Resources\Asistencias\Pages;

use App\Filament\Resources\Asistencias\AsistenciaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Models\Asistencia;
use App\Models\Cliente;
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
                        ->label('Buscar Cliente'),
                    \Filament\Forms\Components\TextInput::make('cantidad_clases')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required()
                        ->label('Cantidad de Clases')
                ])
                ->action(function (array $data) {
                    $cliente = Cliente::find($data['id_cliente']);
                    $hoy = now();
                    
                    $asistenciaAbierta = Asistencia::where('id_cliente', $cliente->id)
                        ->whereNull('fecha_hora_salida')
                        ->whereDate('fecha_hora_ingreso', $hoy->toDateString())
                        ->exists();

                    if ($asistenciaAbierta) {
                        Notification::make()
                            ->title('Error al registrar ingreso')
                            ->body('El cliente ya se encuentra dentro del gimnasio (tiene un ingreso abierto).')
                            ->danger()
                            ->send();
                        return;
                    }
                    $planes = $cliente->planes;
                    
                    if ($planes->isEmpty()) {
                        Notification::make()
                            ->title('Acceso Denegado')
                            ->body('El cliente no tiene ningún plan asignado.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $tieneFacturaValida = false;
                    $ultimaEmisionValida = null;
                    
                    $facturas = $cliente->facturas()->orderBy('fecha_emision', 'desc')->get();
                    
                    foreach ($facturas as $factura) {
                        if (!in_array($factura->estado, ['vigente', 'pagada'])) {
                            continue;
                        }
                        
                        $emision = clone ($factura->fecha_emision ?? $factura->created_at);
                        $emision->startOfDay();
                        
                        $fin = clone $emision;
                        switch (strtolower($factura->periodo)) {
                            case 'diario': $fin->addDay(); break;
                            case 'mensual': $fin->addMonth(); break;
                            case 'trimestral': $fin->addMonths(3); break;
                            case 'semestral': $fin->addMonths(6); break;
                            case 'anual': $fin->addYear(); break;
                            case 'pase libre': $fin->addMonth(); break;
                            default: $fin->addMonth(); break;
                        }
                        $fin->endOfDay();
                        
                        if ($hoy->betweenIncluded($emision, $fin)) {
                            $tieneFacturaValida = true;
                            $ultimaEmisionValida = clone $emision;
                            break;
                        }
                    }

                    if (!$tieneFacturaValida) {
                        Notification::make()
                            ->title('Deuda Detectada')
                            ->body('El cliente no posee una factura vigente o pagada que cubra la fecha actual.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $esIlimitado = $planes->contains('contador', 0);
                    $limiteTope = $planes->sum('contador');
                    
                    $inicio_mes = $ultimaEmisionValida;
                    
                    $visitasEstePeriodo = Asistencia::where('id_cliente', $cliente->id)
                        ->where('created_at', '>=', $inicio_mes)
                        ->sum('clases_consumidas');
                        
                    $restantes = 0;
                    $clases_pedidas = (int) $data['cantidad_clases'];
                    
                    if (!$esIlimitado) {
                        $restantesActuales = $limiteTope - $visitasEstePeriodo;
                        
                        // Check if the user has enough balance for requested classes
                        if ($restantesActuales < $clases_pedidas) {
                            Notification::make()
                                ->title('Límite insuficiente')
                                ->body("El cliente solicita {$clases_pedidas} clases, pero solo dispone de {$restantesActuales} asistencias en su tope de este período.")
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $restantes = $restantesActuales - $clases_pedidas;
                    }
                        
                    Asistencia::create([
                        'id_cliente' => $cliente->id,
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
                        ->label('Buscar Cliente')
                ])
                ->action(function (array $data) {
                    $hoy = now();
                    $asistencia = Asistencia::where('id_cliente', $data['id_cliente'])
                        ->whereNull('fecha_hora_salida')
                        ->whereDate('fecha_hora_ingreso', $hoy->toDateString())
                        ->latest('fecha_hora_ingreso')
                        ->first();
                        
                    if (!$asistencia) {
                        Notification::make()
                            ->title('Error al registrar salida')
                            ->body('El cliente seleccionado no registra un ingreso abierto en el día de la fecha.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $duracion = $asistencia->fecha_hora_ingreso->diffInMinutes($hoy);
                    
                    $asistencia->update([
                        'fecha_hora_salida' => $hoy,
                        'duracion' => $duracion,
                    ]);
                    
                    Notification::make()
                        ->title('Salida registrada con éxito')
                        ->success()
                        ->send();
                }),
        ];
    }
}
