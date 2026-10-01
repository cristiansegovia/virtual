<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Factura;
use Illuminate\Database\Seeder;

class ActualizarContadorAsistenciasSeeder extends Seeder
{
    public function run(): void
    {
        $consumidasPorPeriodo = [];
        $actualizadas = 0;
        $sinCambios = 0;
        $sinPlan = 0;
        $sinFactura = 0;
        $facturaInconsistente = 0;

        Asistencia::query()
            ->with([
                'plan',
                'factura.planes',
                'cliente.facturas' => fn ($query) => $query->with('planes')->orderByDesc('fecha_emision'),
            ])
            ->orderBy('fecha_hora_ingreso')
            ->orderBy('id')
            ->chunk(200, function ($asistencias) use (&$consumidasPorPeriodo, &$actualizadas, &$sinCambios, &$sinPlan, &$sinFactura, &$facturaInconsistente): void {
                foreach ($asistencias as $asistencia) {
                    if (!$asistencia->plan) {
                        $sinPlan++;
                        continue;
                    }

                    [$factura, $motivo] = $this->resolverFactura($asistencia);

                    if (!$factura) {
                        if ($motivo === 'inconsistente') {
                            $facturaInconsistente++;
                        } else {
                            $sinFactura++;
                        }
                        continue;
                    }

                    $clavePeriodo = $factura->id . ':' . $asistencia->plan->id;
                    $clasesConsumidas = (int) ($asistencia->clases_consumidas ?? 1);
                    $consumidasPorPeriodo[$clavePeriodo] = ($consumidasPorPeriodo[$clavePeriodo] ?? 0) + $clasesConsumidas;

                    $tope = $asistencia->plan->contador;
                    $saldo = ($tope === null || $tope === 0)
                        ? 0
                        : max(0, (int) $tope - $consumidasPorPeriodo[$clavePeriodo]);

                    if ((int) $asistencia->contador_asistencias === $saldo) {
                        $sinCambios++;
                        continue;
                    }

                    $actualizadas += Asistencia::query()
                        ->whereKey($asistencia->id)
                        ->update(['contador_asistencias' => $saldo]);
                }
            });

        $this->command->info("Saldos actualizados: {$actualizadas}");
        $this->command->info("Sin cambios: {$sinCambios}");
        $this->command->info("Omitidas por falta de plan: {$sinPlan}");
        $this->command->info("Omitidas por falta de factura aplicable: {$sinFactura}");
        $this->command->info("Omitidas por factura inconsistente: {$facturaInconsistente}");
    }

    private function resolverFactura(object $asistencia): array
    {
        $fechaIngreso = $asistencia->fecha_hora_ingreso ?? $asistencia->created_at;
        $facturaAsignada = $asistencia->factura;

        if ($facturaAsignada) {
            if ((int) $facturaAsignada->cliente_id !== (int) $asistencia->id_cliente
                || !$facturaAsignada->coversDate($fechaIngreso)
                || ($facturaAsignada->planes->isNotEmpty()
                    && !$facturaAsignada->planes->contains('id', $asistencia->id_plan))) {
                return [null, 'inconsistente'];
            }

            return [$facturaAsignada, null];
        }

        $facturaEncontrada = $asistencia->cliente?->facturas?->first(
            fn (Factura $factura): bool => $factura->coversDate($fechaIngreso)
                && ($factura->planes->isEmpty() || $factura->planes->contains('id', $asistencia->id_plan))
        );

        return $facturaEncontrada
            ? [$facturaEncontrada, null]
            : [null, 'no_encontrada'];
    }
}
