<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use Illuminate\Database\Seeder;

class AsignarPlanesAsistenciasSeeder extends Seeder
{
    public function run(): void
    {
        $actualizadas = 0;
        $sinCliente = 0;
        $sinPlanes = 0;

        Asistencia::query()
            ->whereNull('id_plan')
            ->with('cliente.planes')
            ->chunkById(200, function ($asistencias) use (&$actualizadas, &$sinCliente, &$sinPlanes): void {
                foreach ($asistencias as $asistencia) {
                    $cliente = $asistencia->cliente;

                    if (!$cliente) {
                        $sinCliente++;
                        continue;
                    }

                    if ($cliente->planes->isEmpty()) {
                        $sinPlanes++;
                        continue;
                    }

                    $plan = $cliente->planes->random();
                    $actualizadas += Asistencia::query()
                        ->whereKey($asistencia->id)
                        ->whereNull('id_plan')
                        ->update(['id_plan' => $plan->id]);
                }
            });

        $this->command->info("Asistencias actualizadas: {$actualizadas}");
        $this->command->info("Omitidas por cliente inexistente: {$sinCliente}");
        $this->command->info("Omitidas porque el cliente no tiene planes: {$sinPlanes}");
    }
}
