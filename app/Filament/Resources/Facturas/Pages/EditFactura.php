<?php

namespace App\Filament\Resources\Facturas\Pages;

use App\Filament\Resources\Facturas\FacturaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFactura extends EditRecord
{
    protected static string $resource = FacturaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Generar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('facturas.pdf', ['factura' => $this->record]))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
