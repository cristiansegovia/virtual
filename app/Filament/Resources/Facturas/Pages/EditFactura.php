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
            Action::make('marcar_pagado')
                ->label('Marcar Pagado')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->hidden(fn () => $this->record->estado === 'pagada')
                ->action(function () {
                    $this->record->update(['estado' => 'pagada']);
                    \Filament\Notifications\Notification::make()
                        ->title('Factura Pagada')
                        ->body('El estado se ha actualizado a "Pagada".')
                        ->success()
                        ->send();
                    
                    redirect(request()->header('Referer'));
                })
                ->requiresConfirmation(),
            Action::make('cancelar_factura')
                ->label('Cancelada')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->hidden(fn () => $this->record->estado === 'cancelada')
                ->action(function () {
                    $this->record->update(['estado' => 'cancelada']);
                    \Filament\Notifications\Notification::make()
                        ->title('Factura Cancelada')
                        ->body('El estado se ha actualizado a "Cancelada".')
                        ->success()
                        ->send();
                        
                    redirect(request()->header('Referer'));
                })
                ->requiresConfirmation(),
        ];
    }
}
