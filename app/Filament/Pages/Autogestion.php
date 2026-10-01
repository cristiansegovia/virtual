<?php

namespace App\Filament\Pages;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Plan;
use Filament\Pages\Page;

class Autogestion extends Page
{
    protected string $view = 'filament.pages.autogestion';
    protected static string $layout = 'filament-panels::components.layout.base';

    protected static ?string $navigationLabel = 'Autogestión';
    protected static ?string $title = 'Autogestión de Clientes';
    protected static ?string $slug = 'autogestion';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-finger-print';
    protected static ?int $navigationSort = 2;

    public string $dni = '';
    public string $paso = 'dni'; // 'dni', 'seleccion_plan', 'exito', 'error'
    
    public ?int $cliente_id = null;
    public ?string $cliente_nombre = null;
    public ?string $cliente_foto = null;
    public array $planes_disponibles = [];
    
    public ?int $plan_id = null;
    public ?string $plan_nombre = null;
    public int $cantidad_clases = 1;
    
    public ?string $mensaje_error = null;
    public ?string $subtitulo_error = null;
    public array $datos_exito = [];

    public function appendDigit(string $digit): void
    {
        if ($this->paso !== 'dni' || strlen($this->dni) >= 10) {
            return;
        }
        $this->dni .= $digit;
    }

    public function deleteDigit(): void
    {
        if ($this->paso !== 'dni') {
            return;
        }
        $this->dni = substr($this->dni, 0, -1);
    }

    public function clearDni(): void
    {
        $this->dni = '';
    }

    public function incrementClases(): void
    {
        $this->cantidad_clases++;
    }

    public function decrementClases(): void
    {
        if ($this->cantidad_clases > 1) {
            $this->cantidad_clases--;
        }
    }

    public function buscarCliente(): void
    {
        $dniLimpio = trim($this->dni);
        if (empty($dniLimpio)) {
            $this->mostrarError('DNI Requerido', 'Por favor, ingrese su número de DNI para continuar.');
            return;
        }

        $cliente = Cliente::with('planes')->where('dni', $dniLimpio)->first();

        if (!$cliente) {
            $this->mostrarError(
                'Cliente no encontrado',
                'El DNI ' . $dniLimpio . ' no se encuentra registrado en el sistema. Por favor, acérquese a recepción para darse de alta.'
            );
            return;
        }

        if (!$cliente->estado) {
            $this->mostrarError(
                'Acceso Inactivo',
                'Hola ' . $cliente->nombre . ', tu cuenta se encuentra inactiva. Por favor, consulta en recepción para reactivarla.'
            );
            return;
        }

        if ($cliente->planes->isEmpty()) {
            $this->mostrarError(
                'Sin Plan Asignado',
                'Hola ' . $cliente->nombre . ', no posees ningún plan o actividad asignada en el sistema. Por favor, acércate a recepción.'
            );
            return;
        }

        $this->cliente_id = $cliente->id;
        $this->cliente_nombre = trim("{$cliente->nombre} {$cliente->apellido}");
        $this->cliente_foto = $cliente->foto_perfil;

        // Si tiene un único plan, no se muestra el selector de plan y se procesa directamente
        if ($cliente->planes->count() === 1) {
            $planUnico = $cliente->planes->first();
            $this->plan_id = $planUnico->id;
            $this->plan_nombre = $planUnico->nombre;
            $this->procesarIngreso();
            return;
        }

        // Si tiene múltiples planes, se le muestra la pantalla para elegir la actividad
        $this->planes_disponibles = $cliente->planes->map(function ($p) {
            return [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria,
                'contador' => $p->contador,
                'es_ilimitado' => ($p->contador === 0 || $p->contador === null),
            ];
        })->values()->toArray();

        $this->paso = 'seleccion_plan';
    }

    public function selectPlan(int $planId): void
    {
        $this->plan_id = $planId;
        $plan = Plan::find($planId);
        $this->plan_nombre = $plan?->nombre;
        $this->procesarIngreso();
    }

    public function procesarIngreso(): void
    {
        if (!$this->cliente_id || !$this->plan_id) {
            $this->mostrarError('Error de Datos', 'No se ha podido identificar el cliente o el plan seleccionado.');
            return;
        }

        $cliente = Cliente::with('planes', 'facturas')->find($this->cliente_id);
        $plan = Plan::find($this->plan_id);
        $hoy = now();

        if (!$cliente || !$plan) {
            $this->mostrarError('Error', 'No se encontró el registro del cliente o plan.');
            return;
        }

        // 1. Verificar si ya tiene un ingreso abierto hoy para esta actividad
        $asistenciaAbierta = Asistencia::where('id_cliente', $cliente->id)
            ->where('id_plan', $plan->id)
            ->whereNull('fecha_hora_salida')
            ->whereDate('fecha_hora_ingreso', $hoy->toDateString())
            ->exists();

        if ($asistenciaAbierta) {
            $this->mostrarError(
                'Ingreso ya Registrado',
                'Ya registras un ingreso abierto hoy para la actividad "' . $plan->nombre . '". Si ya finalizaste, solicita la salida en recepción.'
            );
            return;
        }

        // 2. Validar factura vigente o pagada que cubra hoy y cubra el plan
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
            $this->mostrarError(
                'Factura Vencida o Pendiente',
                'No posees una factura vigente o pagada que cubra la actividad "' . $plan->nombre . '" para la fecha actual. Por favor, regulariza tu situación en recepción.'
            );
            return;
        }

        // 3. Control de contadores independientes por plan
        $esIlimitado = ($plan->contador === 0 || $plan->contador === null);
        $limiteTope = (int) $plan->contador;
        $inicio_periodo = $ultimaEmisionValida ?? $hoy->copy()->startOfMonth();

        $visitasEstePeriodo = Asistencia::where('id_cliente', $cliente->id)
            ->where('id_plan', $plan->id)
            ->where('created_at', '>=', $inicio_periodo)
            ->sum('clases_consumidas');

        $restantes = 0;
        $clases_pedidas = (int) max(1, $this->cantidad_clases);

        if (!$esIlimitado) {
            $restantesActuales = $limiteTope - $visitasEstePeriodo;

            if ($restantesActuales < $clases_pedidas) {
                $this->mostrarError(
                    'Límite de Clases Alcanzado',
                    "Solicitas {$clases_pedidas} clase(s), pero solo dispones de {$restantesActuales} asistencia(s) en tu plan '{$plan->nombre}' para este período."
                );
                return;
            }

            $restantes = $restantesActuales - $clases_pedidas;
        }

        // 4. Crear Asistencia
        Asistencia::create([
            'id_cliente' => $cliente->id,
            'id_plan' => $plan->id,
            'id_factura' => $facturaValida?->id,
            'fecha_hora_ingreso' => $hoy,
            'fecha_hora_salida' => null,
            'origen' => 'autogestion',
            'contador_asistencias' => $restantes,
            'clases_consumidas' => $clases_pedidas,
            'estado' => true,
            'duracion' => null,
            'created_at' => $hoy,
        ]);

        // 5. Preparar datos de éxito
        $this->datos_exito = [
            'nombre' => $cliente->nombre,
            'apellido' => $cliente->apellido,
            'dni' => $cliente->dni,
            'foto' => $cliente->foto_perfil,
            'plan' => $plan->nombre,
            'categoria' => $plan->categoria,
            'restantes' => $esIlimitado ? 'Ilimitadas' : $restantes,
            'tope' => $esIlimitado ? 'Ilimitado' : $limiteTope,
            'clases' => $clases_pedidas,
            'hora' => $hoy->format('H:i'),
            'fecha' => $hoy->isoFormat('D [de] MMMM'),
        ];

        $this->paso = 'exito';
    }

    public function mostrarError(string $titulo, string $subtitulo): void
    {
        $this->mensaje_error = $titulo;
        $this->subtitulo_error = $subtitulo;
        $this->paso = 'error';
    }

    public function reiniciar(): void
    {
        $this->reset([
            'dni',
            'paso',
            'cliente_id',
            'cliente_nombre',
            'cliente_foto',
            'planes_disponibles',
            'plan_id',
            'plan_nombre',
            'cantidad_clases',
            'mensaje_error',
            'subtitulo_error',
            'datos_exito',
        ]);
        $this->cantidad_clases = 1;
        $this->paso = 'dni';
    }
}
