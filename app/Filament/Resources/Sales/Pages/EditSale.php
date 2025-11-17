<?php

namespace App\Filament\Resources\Sales\Pages;

use Filament\Actions\EditAction;
use Log;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Sales\SaleResource;
use App\Services\Inventory\KardexService;
use App\Models\CashBox;
use App\Models\CashBoxCorrelative;
use App\Models\Contingency;
use App\Models\Customer;
use App\Models\DteTransmisionWherehouse;
use App\Models\Inventory;
use App\Models\InventoryGrouped;
use App\Models\Provider;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Service\GetCashBoxOpenedService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use http\Client;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Mockery\Exception;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    protected function getFormActions(): array
    {
        return [
            // Acción para finalizar la venta
            Action::make('save')
                ->label('Finalizar Venta')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Confirmación')
                ->modalSubheading('¿Estás seguro de que deseas Finalizar esta venta?')
                ->modalButton('Sí, Finalizar venta')
                ->action(function (EditAction $edit) {
                    try {
                        DB::beginTransaction();

                        if ($this->record->sale_total <= 0) {
                            Notification::make('No se puede finalizar la venta')
                                ->title('Error al finalizar venta')
                                ->body('El monto total de la venta debe ser mayor a 0')
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

                        $operation_condition_id = $this->data['operation_condition_id'];
                        if ($operation_condition_id == "") {
                            Notification::make('No se puede finalizar la venta')
                                ->title('Condición de operación')
                                ->body('No se puede finalizar la venta, selecciona la condicion de la venta')
                                ->danger()
                                ->send();
                            return;
                        }

                        $payment_method_id = $this->data['payment_method_id'];

                        if ($payment_method_id == "") {
                            Notification::make('No se puede finalizar la venta')
                                ->title('Forma de pago')
                                ->body('No se puede finalizar la venta, selecciona la forma de pago')
                                ->danger()
                                ->send();
                            return;
                        }


                        $openedCashBox = (new GetCashBoxOpenedService())->getOpenCashBoxId(false);
                        if (!$openedCashBox) {
                            Notification::make('No se puede finalizar la venta')
                                ->title('Caja cerrada')
                                ->body('No se puede finalizar la venta porque no hay caja abierta')
                                ->danger()
                                ->send();
                            return;
                        }


                        if ($this->record->sale_total <= 0) {
                            Notification::make('No se puede finalizar la venta')
                                ->title('Error al finalizar venta')
                                ->body('El monto total de la venta debe ser mayor a 0')
                                ->danger()
                                ->send();

                            return;
                        }

                        if ($this->data['operation_condition_id'] == 1) {
                            $sale_total = isset($this->data['sale_total'])
                                ? doubleval($this->data['sale_total'])
                                : 0.0;
                            $cash = isset($this->data['cash'])
                                ? doubleval($this->data['cash'])
                                : 0.0;

                            if ($cash < $sale_total) {
                                Notification::make('No se puede finalizar la venta')
                                    ->title('Error al finalizar venta')
                                    ->body('El monto en efectivo es menor al total de la venta')
                                    ->danger()
                                    ->send();
                                return;
                            }
                        } else {
//                        $salePayment_status='Pendiente';
                            $status_sale_credit = 1;
                        }

                        //Obtenre modeloFacturacion
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
                        $sale->payment_method_id = $payment_method_id;
                        $sale->operation_condition_id = $operation_condition_id;
                        $sale->billing_model = $billing_model;
                        $sale->transmision_type = $transmision_type;
                        $sale->save();

//                    $document_type_id =$this->record->document_type_id;
                        $document_internal_number_new = 0;
                        $idCajaAbierta = (new GetCashBoxOpenedService())->getOpenCashBoxId(true);

                        // Usar lockForUpdate para prevenir race condition
                        $CashBoxCOrrelativeOpen = CashBoxCorrelative::where('cash_box_id', $idCajaAbierta)
                            ->where('document_type_id', $documentType)
                            ->lockForUpdate()
                            ->first();

                        if ($CashBoxCOrrelativeOpen) {
                            $document_internal_number_new = $CashBoxCOrrelativeOpen->current_number + 1;
                        }


                        $salesItem = SaleItem::where('sale_id', $sale->id)->get();

                        foreach ($salesItem as $item) {
                            try {
                                // El servicio maneja automáticamente productos agrupados y simples
                                app(KardexService::class)->registrarVenta($sale, $item);
                            } catch (\Exception $e) {
                                Log::error("Error al crear Kardex para venta: {$item->id}", [
                                    'error' => $e->getMessage()
                                ]);
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

                        //obtener id de la caja y buscar la caja
//                        $correlativo = CashBoxCorrelative::where('cash_box_id', $idCajaAbierta)->where('document_type_id', $documentType)->first();
                        $CashBoxCOrrelativeOpen->current_number = $document_internal_number_new;
                        $CashBoxCOrrelativeOpen->save();
                        Notification::make()
                            ->title('Venta Finalizada')
                            ->body('Venta finalizada con éxito. # Comprobante **' . $document_internal_number_new . '**')
                            ->success()
                            ->send();
                        // Redirigir después de completar el proceso
                        DB::commit();

                        $this->redirect(static::getResource()::getUrl('index'));
                    } catch (Exception) {
                        DB::rollBack();

                        Notification::make('No se puede finalizar la venta')
                            ->title('Error al finalizar venta')
                            ->body('Ocurrió un error al intentar finalizar la venta.')
                            ->danger()
                            ->send();
                        return;
                    }


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