<?php

namespace App\Filament\Resources\AdjustmentInventories\Pages;

use Filament\Actions\EditAction;
use Log;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\AdjustmentInventories\AdjustmentInventoryResource;
use App\Services\Inventory\KardexService;
use App\Models\adjustmentInventory;
use App\Models\adjustmentInventoryItems;
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
use Livewire\Attributes\On;

class EditAdjustmentInventory extends EditRecord
{
    protected static string $resource = AdjustmentInventoryResource::class;

    protected function getFormActions(): array
    {
        return [
            // Acción para finalizar la venta
            Action::make('save')
                ->label('Finalizar Proceso')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Confirmación')
                ->modalSubheading('¿Estás seguro de que deseas Finalizar esta proceso?')
                ->modalButton('Sí, Finalizar venta')
                ->action(function (EditAction $edit) {

                    $id_sale = $this->record->id; // Obtener el registro de la compra
                    $ajusteProceso = adjustmentInventory::find($id_sale);
                    if ($ajusteProceso->monto <= 0) {
                        Notification::make('No se puede finalizar la venta')
                            ->title('Error al finalizar proceso')
                            ->body('El monto total del proceso debe ser mayor a 0')
                            ->danger()
                            ->send();

                        return;
                    }

                    $salesItem = adjustmentInventoryItems::where('adjustment_id', $this->record->id)->get();
                    $documnetType = $ajusteProceso->documenttype->name ?? 'S/N';
//                    $entity = $client->name??'' . ' ' . $client->last_name??'';
                    $entity = $ajusteProceso->entidad;
                    $tipoProceso = $ajusteProceso->tipo;
                    $pais = "Salvadoreña";
                    $isSalida = $tipoProceso === 'Salida';
                    foreach ($salesItem as $item) {
                        $inventory = Inventory::with('product')->find($item->inventory_id);
                        //verificar si es un producto compuesto
                        $is_grouped = $inventory->product->is_grouped;
                        $operationType = $isSalida ? 'Salida' : 'Entrada';
                        $documentNumber =  $ajusteProceso->id;

                        if ($is_grouped) {
                            $inventoriesGrouped = InventoryGrouped::with('inventoryChild.product')
                                ->where('inventory_grouped_id', $item->inventory_id)
                                ->get();

                            foreach ($inventoriesGrouped as $inventarioHijo) {
                                $child = $inventarioHijo->inventoryChild;
                                $cantidad = $item->cantidad;

                                try {
                                    // Crear item temporal para el servicio
                                    $tempItem = (object)[
                                        'inventory_id' => $child->id,
                                        'inventory' => $child,
                                        'quantity' => $cantidad,
                                        'price' => $item->precio_unitario,
                                        'adjustment_quantity' => $cantidad,
                                        'id' => $item->id
                                    ];

                                    app(KardexService::class)->registrarAjuste(
                                        $ajusteProceso,
                                        $tempItem,
                                        !$isSalida // esEntrada
                                    );
                                } catch (\Exception $e) {
                                    Log::error("Error al crear Kardex para ajuste agrupado: {$item->id}", [
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }
                        } else {
                            try {
                                app(KardexService::class)->registrarAjuste(
                                    $ajusteProceso,
                                    $item,
                                    !$isSalida // esEntrada
                                );
                            } catch (\Exception $e) {
                                Log::error("Error al crear Kardex para ajuste: {$item->id}", [
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }


                    }

                    $ajusteProceso->status = "FINALIZADO";
                    $ajusteProceso->save();

                    Notification::make()
                        ->title($tipoProceso)
                        ->body($tipoProceso . ' finalizada con éxito.')
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
