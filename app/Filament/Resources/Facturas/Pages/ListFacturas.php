<?php

namespace App\Filament\Resources\Facturas\Pages;

use App\Filament\Resources\Facturas\FacturaResource;
use App\Models\Factura;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFacturas extends ListRecords
{
    protected static string $resource = FacturaResource::class;

    public function mount(): void
    {
        parent::mount();

        // Actualizar automáticamente facturas vencidas
        Factura::where('estado', 'vigente')
            ->where('fecha_vencimiento', '<', now())
            ->update(['estado' => 'vencida']);

        // Recalcular totales para facturas con total = 0
        Factura::where('total', 0)->get()->each(function ($factura) {
            $factura->save();
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
