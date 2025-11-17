<?php

namespace App\Filament\Resources\Purchases\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Log;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Services\Inventory\KardexService;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class AdjustPurchase extends EditRecord
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
            Action::make('cancel')
                ->label('Cancelar')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->url(fn() => static::getResource()::getUrl('index')),
        ];
    }

    /**
     * Form actions personalizadas: Guardar Ajuste
     */
    protected function getFormActions(): array
    {
        return [
            // Botón Guardar Ajuste
            Action::make('save_adjust')
                ->label('Guardar Ajuste y Regenerar Kardex')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Ajuste de Compra')
                ->modalDescription('Esta acción eliminará el Kardex anterior, guardará los cambios y regenerará el Kardex con los nuevos datos. Los movimientos posteriores serán recalculados automáticamente.')
                ->modalSubmitActionLabel('Sí, Guardar Ajuste')
                ->extraAttributes([
                    'wire:loading.attr' => 'disabled',
                    'wire:loading.class' => 'opacity-50 cursor-not-allowed',
                ])
                ->action(function () {
                    $purchase = $this->record;

                    try {
                        // 1. Validar que tenga items
                        $purchaseItems = PurchaseItem::where('purchase_id', $purchase->id)->get();

                        if ($purchaseItems->isEmpty()) {
                            Notification::make('sin_items')
                                ->title('Error: Sin productos')
                                ->body('No se puede ajustar una compra sin productos.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // 2. Eliminar kardex anterior
                        $kardexService = app(KardexService::class);
                        $registrosEliminados = $kardexService->eliminarKardexCompra($purchase);

                        Log::info("Kardex eliminado para ajuste", [
                            'purchase_id' => $purchase->id,
                            'registros_eliminados' => $registrosEliminados
                        ]);

                        // 3. Guardar cambios en la compra
                        $this->save();

                        // 4. Recargar la compra para obtener datos frescos
                        $purchase->refresh();

                        // 5. Regenerar kardex con nuevos datos (automáticamente recalcula posteriores)
                        $kardexService->ajustarCompra($purchase);

                        // Notificación de éxito
                        Notification::make('ajuste_exitoso')
                            ->title('Compra ajustada exitosamente')
                            ->body('El Kardex fue regenerado y los movimientos posteriores fueron recalculados correctamente.')
                            ->success()
                            ->send();

                        // Redirigir al listado
                        return redirect()->to(static::getResource()::getUrl('index'));

                    } catch (\Exception $e) {
                        Log::error("Error al ajustar compra: {$purchase->id}", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);

                        Notification::make('error_ajuste')
                            ->title('Error al ajustar compra')
                            ->body("Error: {$e->getMessage()}")
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Ajustar Compra';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Ajustar Compra Finalizada';
    }

    public function getSubheading(): string|Htmlable
    {
        return 'Modifique los productos, cantidades y costos. Al guardar se regenerará el Kardex automáticamente.';
    }

    #[On('refreshPurchase')]
    public function refresh(): void
    {
    }

    /**
     * Validar que solo se pueda ajustar si el Kardex fue generado
     */
    public function mount(int | string $record): void
    {
        parent::mount($record);

        $purchase = $this->record;

        // Solo permitir ajuste si el Kardex fue generado y no está anulado
        if (!$purchase->kardex_generated || $purchase->status === 'Anulado') {
            $this->redirect(static::getResource()::getUrl('index'));

            if (!$purchase->kardex_generated) {
                Notification::make('ajuste_bloqueado')
                    ->title('Ajuste no disponible')
                    ->body('Solo se pueden ajustar compras finalizadas con Kardex generado.')
                    ->warning()
                    ->send();
            } else {
                Notification::make('compra_anulada')
                    ->title('Compra anulada')
                    ->body('No se puede ajustar una compra anulada.')
                    ->warning()
                    ->send();
            }
        }
    }
}
