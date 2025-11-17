<?php

namespace App\Console\Commands;

use App\Models\Kardex;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateKardex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kardex:recalculate
                            {--inventory_id= : ID del inventario a recalcular}
                            {--date= : Recalcular desde una fecha específica (YYYY-MM-DD)}
                            {--all : Recalcular todo el kardex}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula el kardex completo: previous_stock, stock_actual, promedial_cost, money_actual y actualiza tabla inventories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $inventoryId = $this->option('inventory_id');
        $date = $this->option('date');
        $all = $this->option('all');

        // Validar que se proporcione al menos una opción
        if (!$inventoryId && !$date && !$all) {
            $this->error('Debes proporcionar al menos una opción: --inventory_id, --date o --all');
            $this->info('Ejemplos:');
            $this->line('  php artisan kardex:recalculate --inventory_id=123');
            $this->line('  php artisan kardex:recalculate --date=2025-09-01');
            $this->line('  php artisan kardex:recalculate --all');
            return 1;
        }

        // Confirmar acción si es --all
        if ($all) {
            $this->warn('⚠️  ATENCIÓN: Vas a recalcular TODO el kardex');
            $this->line('   Esto incluye:');
            $this->line('   • Recálculo de saldos (previous_stock y stock_actual)');
            $this->line('   • Recálculo de costo promedio ponderado (promedial_cost)');
            $this->line('   • Recálculo de valores monetarios (money_in, money_out, money_actual)');
            $this->line('   • Actualización de stock en tabla inventories');
            $this->newLine();

            if (!$this->confirm('¿Estás seguro de continuar? Esto puede tardar varios minutos.')) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        $this->info('🚀 Iniciando recálculo del kardex...');
        $this->newLine();

        try {
            DB::beginTransaction();

            if ($inventoryId) {
                $this->recalculateInventory($inventoryId);
            } elseif ($date) {
                $this->recalculateFromDate($date);
            } elseif ($all) {
                $this->recalculateAll();
            }

            DB::commit();

            $this->newLine();
            $this->info('═══════════════════════════════════════════════');
            $this->info('✓ RECÁLCULO COMPLETADO EXITOSAMENTE');
            $this->info('═══════════════════════════════════════════════');
            $this->line('✓ Previous_stock recalculado');
            $this->line('✓ Stock_actual recalculado');
            $this->line('✓ Promedial_cost recalculado (costo promedio ponderado)');
            $this->line('✓ Money_actual recalculado');
            $this->line('✓ Stock en tabla inventories actualizado');
            $this->newLine();
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error durante el recálculo: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Recalcula el kardex de un inventario específico
     */
    private function recalculateInventory(int $inventoryId)
    {
        $kardexRecords = Kardex::with('inventory.product')
            ->where('inventory_id', $inventoryId)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($kardexRecords->isEmpty()) {
            $this->warn("  ⊗ No se encontraron registros para el inventario {$inventoryId}");
            return;
        }

        // Obtener información del producto
        $firstRecord = $kardexRecords->first();
        $productName = $firstRecord->inventory->product->name ?? "Inventario {$inventoryId}";

        $this->line("  ► Procesando: {$productName} ({$kardexRecords->count()} movimientos)");

        $this->recalculateRecords($kardexRecords);
    }

    /**
     * Recalcula el kardex desde una fecha específica
     */
    private function recalculateFromDate(string $date)
    {
        $this->info("Recalculando desde la fecha: {$date}");

        // Obtener todos los inventarios afectados
        $inventoryIds = Kardex::where('date', '>=', $date)
            ->distinct()
            ->pluck('inventory_id');

        $this->info("Inventarios afectados: {$inventoryIds->count()}");
        $bar = $this->output->createProgressBar($inventoryIds->count());
        $bar->start();

        foreach ($inventoryIds as $inventoryId) {
            $this->recalculateInventory($inventoryId);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Recalcula todo el kardex
     */
    private function recalculateAll()
    {
        $this->warn('═══════════════════════════════════════════════');
        $this->warn('    RECALCULANDO TODO EL KARDEX');
        $this->warn('═══════════════════════════════════════════════');
        $this->newLine();

        $inventoryIds = Kardex::distinct()->pluck('inventory_id');
        $totalRegistros = Kardex::count();

        $this->info("📊 Total de inventarios: {$inventoryIds->count()}");
        $this->info("📊 Total de movimientos: {$totalRegistros}");
        $this->newLine();

        $bar = $this->output->createProgressBar($inventoryIds->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $bar->setMessage('Iniciando...');
        $bar->start();

        $totalCorregidos = 0;
        $startTime = microtime(true);

        foreach ($inventoryIds as $inventoryId) {
            $bar->setMessage("Inventario #{$inventoryId}");
            $this->recalculateInventory($inventoryId);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->info('═══════════════════════════════════════════════');
        $this->info('✓ RECÁLCULO COMPLETADO');
        $this->info('═══════════════════════════════════════════════');
        $this->line("⏱ Tiempo: {$duration} segundos");
        $this->line("📦 Inventarios procesados: {$inventoryIds->count()}");
        $this->line("📝 Movimientos procesados: {$totalRegistros}");
        $this->newLine();
    }

    /**
     * Recalcula una colección de registros de kardex
     * Ahora incluye recálculo de costo promedio ponderado
     */
    private function recalculateRecords($kardexRecords)
    {
        $previousStock = 0;
        $costoPromedioAnterior = 0;
        $corrected = 0;
        $errors = 0;

        foreach ($kardexRecords as $kardex) {
            try {
                // Guardar valores anteriores para comparación
                $oldPreviousStock = $kardex->previous_stock;
                $oldStockActual = $kardex->stock_actual;
                $oldPromedialCost = $kardex->promedial_cost;

                // 1. RECALCULAR: previous_stock
                $kardex->previous_stock = $previousStock;

                // 2. RECALCULAR: stock_actual
                $newStockActual = $previousStock + $kardex->stock_in - $kardex->stock_out;
                $kardex->stock_actual = $newStockActual;

                // 3. RECALCULAR: Costo promedio ponderado
                if ($kardex->stock_in > 0) {
                    // Es una ENTRADA: recalcular costo promedio ponderado
                    $nuevoCosto = $kardex->purchase_price;
                    $totalCantidad = $previousStock + $kardex->stock_in;

                    if ($totalCantidad > 0) {
                        $costoPromedioAnterior = (($previousStock * $costoPromedioAnterior) + ($kardex->stock_in * $nuevoCosto)) / $totalCantidad;
                    } else {
                        $costoPromedioAnterior = $nuevoCosto;
                    }
                } else {
                    // Es una SALIDA: mantener el costo promedio anterior
                    // No se modifica $costoPromedioAnterior
                }

                $kardex->promedial_cost = round($costoPromedioAnterior, 2);

                // 4. RECALCULAR: Valores monetarios
                $kardex->money_in = $kardex->stock_in > 0 ? round($kardex->stock_in * $kardex->purchase_price, 2) : 0;
                $kardex->money_out = $kardex->stock_out > 0 ? round($kardex->stock_out * $kardex->sale_price, 2) : 0;
                $kardex->money_actual = round($newStockActual * $kardex->promedial_cost, 2);

                // 5. VALIDAR: Integridad del cálculo
                $calculado = $kardex->previous_stock + $kardex->stock_in - $kardex->stock_out;
                if ($kardex->stock_actual !== $calculado) {
                    $this->warn("  ⚠ Integridad fallida en ID {$kardex->id}: esperado {$calculado}, obtenido {$kardex->stock_actual}");
                }

                // Guardar solo si hubo cambios
                if ($oldPreviousStock != $kardex->previous_stock ||
                    $oldStockActual != $kardex->stock_actual ||
                    $oldPromedialCost != $kardex->promedial_cost) {
                    $kardex->save();
                    $corrected++;
                }

                // El stock actual de este registro es el previous_stock del siguiente
                $previousStock = $newStockActual;

            } catch (\Exception $e) {
                $errors++;
                $this->error("  ✗ Error en registro ID {$kardex->id}: " . $e->getMessage());
            }
        }

        if ($corrected > 0) {
            $this->line("  → Corregidos: {$corrected} registros");
        }
        if ($errors > 0) {
            $this->error("  → Errores: {$errors} registros");
        }

        // Actualizar el stock en la tabla inventories
        if ($kardexRecords->isNotEmpty() && $previousStock !== null) {
            $this->updateInventoryStock($kardexRecords->first()->inventory_id, $previousStock);
        }
    }

    /**
     * Actualiza el stock en la tabla inventories
     */
    private function updateInventoryStock(int $inventoryId, int $finalStock)
    {
        try {
            DB::table('inventories')
                ->where('id', $inventoryId)
                ->update(['stock' => $finalStock]);

            $this->line("  ✓ Stock actualizado en inventories: {$finalStock}");
        } catch (\Exception $e) {
            $this->warn("  ⚠ No se pudo actualizar stock en inventories: " . $e->getMessage());
        }
    }
}
