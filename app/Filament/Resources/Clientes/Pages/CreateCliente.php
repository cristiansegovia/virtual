<?php

namespace App\Filament\Resources\Clientes\Pages;

use App\Filament\Resources\Clientes\ClienteResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreateCliente extends CreateRecord
{
    protected static string $resource = ClienteResource::class;

    protected function getFormActions(): array
    {
        return array_merge(parent::getFormActions(), [
            Action::make('createAndInvoice')
                ->label('Crear y Emitir Factura')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->action(function () {
                    $this->create();
                    
                    return redirect()->to(\App\Filament\Resources\Facturas\FacturaResource::getUrl('create', ['cliente_id' => $this->record->id]));
                }),
        ]);
    }
}
