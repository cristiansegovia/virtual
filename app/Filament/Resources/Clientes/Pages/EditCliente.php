<?php

namespace App\Filament\Resources\Clientes\Pages;

use App\Filament\Resources\Clientes\ClienteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('crear_factura')
                ->label('Crear Factura')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->url(fn () => \App\Filament\Resources\Facturas\FacturaResource::getUrl('create', ['cliente_id' => $this->record->id])),
            DeleteAction::make(),
        ];
    }
}
