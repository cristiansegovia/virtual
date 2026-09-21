<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AsignarFacturasAsistenciasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $asistenciasSinFactura = Asistencia::whereNull('id_factura')->get();
        $this->command->info("Procesando {$asistenciasSinFactura->count()} asistencias sin factura...");

        $actualizadas = 0;
        $noAsignadas = 0;

        foreach ($asistenciasSinFactura as $asistencia) {
            $cliente = $asistencia->cliente;
            if (!$cliente) {
                $noAsignadas++;
                continue;
            }

            $fechaIngreso = $asistencia->fecha_hora_ingreso ?? $asistencia->created_at;
            $facturas = $cliente->facturas()->orderBy('fecha_emision', 'desc')->get();

            if ($facturas->isEmpty()) {
                $noAsignadas++;
                continue;
            }

            // 1. Buscar factura cuyo período cubra la fecha de ingreso
            $facturaAsignada = null;
            foreach ($facturas as $factura) {
                if ($factura->coversDate($fechaIngreso)) {
                    $facturaAsignada = $factura;
                    break;
                }
            }

            // 2. Si no coincide exactamente con el rango, buscar la factura con fecha de emisión más cercana anterior o igual
            if (!$facturaAsignada) {
                $facturaAsignada = $facturas
                    ->where('fecha_emision', '<=', Carbon::parse($fechaIngreso)->toDateString())
                    ->first();
            }

            // 3. Si aún no hay, tomar la primera factura disponible del cliente
            if (!$facturaAsignada) {
                $facturaAsignada = $facturas->first();
            }

            if ($facturaAsignada) {
                $asistencia->updateQuietly(['id_factura' => $facturaAsignada->id]);
                $actualizadas++;
            } else {
                $noAsignadas++;
            }
        }

        $this->command->info("Proceso completado: {$actualizadas} asistencias actualizadas con id_factura, {$noAsignadas} no asignadas.");
    }
}
