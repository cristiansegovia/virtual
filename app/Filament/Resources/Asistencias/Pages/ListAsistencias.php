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
                        ->label('Buscar Cliente')
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
                    
                    $dia_img = $cliente->fecha_de_ingreso ? $cliente->fecha_de_ingreso->day : 1;
                    $max_days_current_month = $hoy->copy()->endOfMonth()->day;
                    
                    $dia_X = min($dia_img, $max_days_current_month);
                    
                    if ($hoy->day >= $dia_X) {
                        $inicio_mes = $hoy->copy()->day($dia_X)->startOfDay();
                    } else {
                        $last_month = $hoy->copy()->subMonth();
                        $dia_X_last_month = min($dia_img, $last_month->endOfMonth()->day);
                        $inicio_mes = $last_month->day($dia_X_last_month)->startOfDay();
                    }
                    
                    $count = Asistencia::where('id_cliente', $cliente->id)
                        ->where('created_at', '>=', $inicio_mes)
                        ->count() + 1;
                        
                    Asistencia::create([
                        'id_cliente' => $cliente->id,
                        'fecha_hora_ingreso' => $hoy,
                        'fecha_hora_salida' => null,
                        'origen' => 'admin',
                        'contador_asistencias' => $count,
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
