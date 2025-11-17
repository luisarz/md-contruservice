<?php

namespace App\Filament\Resources\Purchases\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Log;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Services\Inventory\KardexService;
use App\Models\Inventory;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    // Deshabilitar notificación automática de Filament
    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Form actions personalizadas: Guardar y Finalizar juntos
     */
    protected function getFormActions(): array
    {
        return [
            // Botón Guardar personalizado
            Action::make('save')
                ->label('Guardar cambios y seguir editando')
                ->icon('heroicon-o-check')
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                    'wire:loading.class' => 'opacity-50 cursor-not-allowed',
                ])
                ->action(function () {
                    $this->save();

                    // Notificación de éxito
                    Notification::make('compra_guardada')
                        ->title('Compra guardada exitosamente')
                        ->body('Los cambios en la compra han sido guardados correctamente.')
                        ->success()
                        ->send();

                    // Redirigir al listado después de guardar
                    return redirect()->to(static::getResource()::getUrl('index'));
                }),

            // ACCIÓN FINALIZAR: Genera Kardex y cambia estado a Finalizado
            Action::make('finalizar')
                ->label('Finalizar y generar Kardex')
                ->requiresConfirmation()
                ->modalHeading('Finalizar Compra')
                ->modalDescription('Al finalizar esta compra se generará el Kardex y actualizará el inventario.')
                ->modalSubmitActionLabel('Sí, Finalizar')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                    'wire:loading.class' => 'opacity-50 cursor-not-allowed',
                ])
                ->visible(fn() => !$this->record->kardex_generated && $this->record->status !== 'Anulado')
                ->action(function () {
                    $purchase = $this->record;
                    $purchaseItems = PurchaseItem::where('purchase_id', $purchase->id)->get();

                    // Validar que tenga items
                    if ($purchaseItems->isEmpty()) {
                        Notification::make('sin_items')
                            ->title('Error: Sin productos')
                            ->body('No se puede finalizar una compra sin productos.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Generar Kardex para cada item
                    foreach ($purchaseItems as $item) {
                        $inventory = Inventory::find($item->inventory_id);

                        if (!$inventory) {
                            Log::error("Inventario no encontrado para el item de compra: {$item->id}");
                            Notification::make('error_inventario')
                                ->title('Error de inventario')
                                ->body("No se encontró el inventario para el producto ID {$item->inventory_id}")
                                ->danger()
                                ->send();
                            return;
                        }

                        // Crear Kardex y actualizar stock
                        try {
                            app(KardexService::class)->registrarCompra($purchase, $item);
                        } catch (\Exception $e) {
                            Log::error("Error al crear Kardex para compra: {$item->id}", [
                                'error' => $e->getMessage()
                            ]);
                            Notification::make('error_kardex')
                                ->title('Error al finalizar')
                                ->body("Error al procesar el Kardex: {$e->getMessage()}")
                                ->danger()
                                ->send();
                            return;
                        }
                    }

                    // Cambiar estado a Finalizado y marcar Kardex como generado
                    $purchase->update([
                        'status' => 'Finalizado',
                        'kardex_generated' => true
                    ]);

                    Notification::make('compra_finalizada')
                        ->title('Compra finalizada exitosamente')
                        ->body('El Kardex fue generado y el inventario actualizado correctamente.')
                        ->success()
                        ->send();

                    // Redirigir al listado
                    return redirect()->to(static::getResource()::getUrl('index'));
                }),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return '';// TODO: Change the autogenerated stub
    }


    #[On('refreshPurchase')]
    public function refresh(): void
    {
    }

    /**
     * Bloquear edición si el Kardex ya fue generado.
     * Solo se puede editar mientras kardex_generated = false.
     */
    public function mount(int | string $record): void
    {
        parent::mount($record);

        $purchase = $this->record;

        // Si ya se generó el Kardex, bloquear edición
        if ($purchase->kardex_generated) {
            $this->redirect(static::getResource()::getUrl('index'));

            \Filament\Notifications\Notification::make('edicion_bloqueada')
                ->title('Edición bloqueada')
                ->body('No se puede editar una compra que ya tiene Kardex generado. Una vez finalizada, la compra no puede modificarse.')
                ->warning()
                ->send();
        }
    }

}
