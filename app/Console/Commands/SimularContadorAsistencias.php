<?php

namespace App\Console\Commands;

use App\Models\Asistencia;
use App\Models\Factura;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SimularContadorAsistencias extends Command
{
    protected $signature = 'asistencias:simular-contadores {--cliente= : Limitar la simulacion al ID de un cliente}';

    protected $description = 'Simula el recálculo de clases restantes por cliente, plan y factura sin modificar datos';

    public function handle(): int
    {
        $consulta = Asistencia::query()
            ->with(['cliente', 'plan', 'factura.planes'])
            ->orderBy('fecha_hora_ingreso')
            ->orderBy('id');

        if ($clienteId = $this->option('cliente')) {
            $consulta->where('id_cliente', $clienteId);
        }

        $asistencias = $consulta->get();

        if ($asistencias->isEmpty()) {
            $this->info('No se encontraron asistencias para simular.');
            return self::SUCCESS;
        }

        $facturasPorCliente = Factura::with('planes')
            ->whereIn('cliente_id', $asistencias->pluck('id_cliente')->unique())
            ->orderByDesc('fecha_emision')
            ->get()
            ->groupBy('cliente_id');

        $consumidasPorPeriodo = [];
        $filas = [];
        $revisar = 0;
        $cambios = 0;

        foreach ($asistencias as $asistencia) {
            $clienteNombre = $asistencia->cliente
                ? trim($asistencia->cliente->nombre . ' ' . $asistencia->cliente->apellido)
                : "Cliente #{$asistencia->id_cliente}";

            if (!$asistencia->plan) {
                $filas[] = [
                    $asistencia->id,
                    $clienteNombre,
                    'Sin plan',
                    $asistencia->id_factura ?? '-',
                    $asistencia->clases_consumidas ?? 1,
                    $asistencia->contador_asistencias,
                    '-',
                    'Revisar: falta plan',
                ];
                $revisar++;
                continue;
            }

            $facturas = $facturasPorCliente->get($asistencia->id_cliente, new Collection());
            [$factura, $motivo] = $this->resolverFactura($asistencia, $facturas);

            if (!$factura) {
                $filas[] = [
                    $asistencia->id,
                    $clienteNombre,
                    $asistencia->plan->nombre,
                    $asistencia->id_factura ?? '-',
                    $asistencia->clases_consumidas ?? 1,
                    $asistencia->contador_asistencias,
                    '-',
                    "Revisar: {$motivo}",
                ];
                $revisar++;
                continue;
            }

            $clavePeriodo = $factura->id . ':' . $asistencia->plan->id;
            $consumidas = (int) ($asistencia->clases_consumidas ?? 1);
            $consumidasPorPeriodo[$clavePeriodo] = ($consumidasPorPeriodo[$clavePeriodo] ?? 0) + $consumidas;

            $tope = $asistencia->plan->contador;
            $saldo = ($tope === null || $tope === 0)
                ? 0
                : max(0, (int) $tope - $consumidasPorPeriodo[$clavePeriodo]);
            $estado = (int) $asistencia->contador_asistencias === $saldo ? 'Sin cambio' : 'Cambiaría';

            if ($estado === 'Cambiaría') {
                $cambios++;
            }

            $filas[] = [
                $asistencia->id,
                $clienteNombre,
                $asistencia->plan->nombre,
                $factura->invoice_number,
                $consumidas,
                $asistencia->contador_asistencias,
                $saldo,
                $estado,
            ];
        }

        $this->warn('SIMULACION: no se modificara ningun registro. Los saldos usan el tope actual de cada plan.');
        $this->table(
            ['Asistencia', 'Cliente', 'Plan', 'Factura', 'Clases', 'Saldo actual', 'Saldo simulado', 'Resultado'],
            $filas,
        );
        $this->newLine();
        $this->info('Asistencias analizadas: ' . $asistencias->count());
        $this->info("Saldos que cambiarian: {$cambios}");
        $this->info("Registros para revisar: {$revisar}");

        return self::SUCCESS;
    }

    private function resolverFactura(object $asistencia, Collection $facturas): array
    {
        $fechaIngreso = $asistencia->fecha_hora_ingreso ?? $asistencia->created_at;
        $facturaAsignada = $asistencia->factura;

        if ($facturaAsignada) {
            if ((int) $facturaAsignada->cliente_id !== (int) $asistencia->id_cliente) {
                return [null, 'la factura asignada pertenece a otro cliente'];
            }

            if (!$facturaAsignada->coversDate($fechaIngreso)) {
                return [null, 'la factura asignada no cubre la fecha del ingreso'];
            }

            if ($facturaAsignada->planes->isNotEmpty() && !$facturaAsignada->planes->contains('id', $asistencia->id_plan)) {
                return [null, 'la factura asignada no incluye el plan'];
            }

            return [$facturaAsignada, null];
        }

        $facturaEncontrada = $facturas->first(function (Factura $factura) use ($fechaIngreso, $asistencia): bool {
            return $factura->coversDate($fechaIngreso)
                && ($factura->planes->isEmpty() || $factura->planes->contains('id', $asistencia->id_plan));
        });

        if (!$facturaEncontrada) {
            return [null, 'no hay factura que cubra la fecha y el plan'];
        }

        return [$facturaEncontrada, null];
    }
}
