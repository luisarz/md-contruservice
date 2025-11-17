<?php

namespace App\Filament\Resources\CreditNotes\Pages;

use Filament\Actions\EditAction;
use Log;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\CreditNotes\CreditNoteResource;
use App\Services\Inventory\KardexService;
use App\Models\CashBoxCorrelative;
use App\Models\Contingency;
use App\Models\Inventory;
use App\Models\InventoryGrouped;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Service\GetCashBoxOpenedService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class EditCreditNote extends EditRecord
{
    protected static string $resource = CreditNoteResource::class;

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    protected function getFormActions(): array
    {
        return [
            // Acción para finalizar la venta
            Action::make('save')
                ->label('Finalizar NC')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Confirmación')
                ->modalSubheading('¿Estás seguro de que deseas Finalizar esta NC?')
                ->modalButton('Sí, Finalizar Nota')
                ->action(function (EditAction $edit) {
                    if ($this->record->sale_total <= 0) {
                        Notification::make('No se puede finalizar la venta')
                            ->title('Error al finalizar Nota')
                            ->body('El monto total de la Nota debe ser mayor a 0')
                            ->danger()
                            ->send();

                        return;
                    }
                    $salePayment_status = 'Pagada';
                    $status_sale_credit = 0;

                    $documentType = $this->data['document_type_id'];
                    if ($documentType == "") {
                        Notification::make('No se puede finalizar la venta')
                            ->title('Tipo de documento')
                            ->body('No se puede finalizar la venta, selecciona el tipo de documento a emitir')
                            ->danger()
                            ->send();
                        return;
                    }


                    $openedCashBox = (new GetCashBoxOpenedService())->getOpenCashBoxId(false);
                    if (!$openedCashBox) {
                        Notification::make('No se puede finalizar la venta')
                            ->title('Caja cerrada')
                            ->body('No se puede finalizar la NOTA porque no hay caja abierta')
                            ->danger()
                            ->send();
                        return;
                    }


                    //Obtener tipo de transmision
                    $wherehouse_id = $this->record->wherehouse_id;
                    $exiteContingencia = Contingency::where('warehouse_id', $wherehouse_id)
                        ->where('is_close', 0)->first();
                    $billing_model = 1;
                    $transmision_type = 1;
                    if ($exiteContingencia) {
                        $exiteContingencia = $exiteContingencia->uuid_hacienda;
                        $transmision_type = 2;
                        $billing_model = 2;
                    }

                    $id_sale = $this->record->id; // Obtener el registro de la compra
                    $sale = Sale::with('documenttype', 'customer', 'customer.country')->find($id_sale);
                    $sale->document_type_id = $documentType;
                    $sale->billing_model = $billing_model;
                    $sale->transmision_type = $transmision_type;
                    $sale->save();

                    // Obtener caja abierta y correlativo con lock
                    $idCajaAbierta = (new GetCashBoxOpenedService())->getOpenCashBoxId(true);
                    $document_internal_number_new = 0;
                    $lastIssuedDocument = CashBoxCorrelative::where('cash_box_id', $idCajaAbierta)
                        ->where('document_type_id', $documentType)
                        ->lockForUpdate()
                        ->first();
                    if ($lastIssuedDocument) {
                        $document_internal_number_new = $lastIssuedDocument->current_number + 1;
                    }


                    $salesItem = SaleItem::where('sale_id', $sale->id)->get();

                    if ($sale->document_type_id == 5) {//Toca el inventario solo si es Nota de credito
                        foreach ($salesItem as $item) {
                            try {
                                // El servicio maneja automáticamente productos agrupados y simples
                                // Usamos registrarAnulacionVenta() porque el efecto es el mismo: devolver stock
                                app(KardexService::class)->registrarAnulacionVenta($sale, $item);
                            } catch (\Exception $e) {
                                Log::error("Error al crear Kardex para nota de crédito: {$item->id}", [
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }


                    $sale->update([
                        'cashbox_open_id' => $openedCashBox,
                        'is_invoiced' => true,
                        'sales_payment_status' => $salePayment_status,
                        'sale_status' => 'Facturada',
                        'status_sale_credit' => $status_sale_credit,
                        'operation_date' => $this->data['operation_date'],
                        'document_internal_number' => $document_internal_number_new
                    ]);

                    // Actualizar correlativo (ya obtenido con lock anteriormente)
                    if ($lastIssuedDocument) {
                        $lastIssuedDocument->current_number = $document_internal_number_new;
                        $lastIssuedDocument->save();
                    }
                    Notification::make()
                        ->title('Nota Finalizada')
                        ->body('Nota finalizada con éxito. # Comprobante **' . $document_internal_number_new . '**')
                        ->success()
                        ->send();

                    // Redirigir después de completar el proceso
                    $this->redirect(static::getResource()::getUrl('index'));
                }),


            // Acción para cancelar la venta
            Action::make('cancelSale')
                ->label('Cancelar venta')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Confirmación')
                ->modalSubheading('¿Estás seguro de que deseas cancelar esta venta? Esta acción no se puede deshacer.')
                ->modalButton('Sí, cancelar venta')
                ->action(function (DeleteAction $delete) {
                    if ($this->record->is_dte) {
                        Notification::make()
                            ->title('Error al anular venta')
                            ->body('No se puede cancelar una venta con DTE.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Eliminar la venta y los elementos relacionados
                    SaleItem::where('sale_id', $this->record->id)->delete();
                    $this->record->delete();

                    Notification::make()
                        ->title('Venta cancelada')
                        ->body('La venta y sus elementos relacionados han sido eliminados con éxito.')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }


    #[On('refreshSale')]
    public function refresh(): void
    {
    }
}
