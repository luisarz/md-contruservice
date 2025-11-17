<?php

namespace App\Services\Inventory;

use App\Models\Inventory;
use App\Models\InventoryGrouped;
use App\Models\Kardex;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\CreditNotePurchase;
use App\Models\CreditNotePurchaseItem;
use App\Models\AdjustmentInventory;
use App\Models\AdjustmentInventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class KardexService
{
    /**
     * Registra inventario inicial (cuando se crea un inventario)
     */
    public function registrarInventarioInicial(Inventory $inventory): bool
    {
        return DB::transaction(function () use ($inventory) {
            return $this->crearRegistroKardex(
                inventory: $inventory,
                operation_type: 'INVENTARIO INICIAL',
                operation_id: $inventory->id,
                operation_detail_id: $inventory->id,
                document_type: 'INVENTARIO INICIAL',
                document_number: 'INV-' . $inventory->id,
                entity: 'Sistema',
                nationality: 'Salvadoreña',
                quantity: $inventory->stock_actual ?? $inventory->stock,
                is_input: true,
                unit_price: $inventory->cost_without_taxes,
                date: now(),
                skip_inventory_update: true // No actualizar stock porque ya viene con el valor
            );
        });
    }

    /**
     * Registra una compra en el Kardex
     */
    public function registrarCompra(Purchase $purchase, PurchaseItem $item): bool
    {
        return DB::transaction(function () use ($purchase, $item) {
            $inventory = $item->inventory;
            $provider = $purchase->provider()->with('pais')->first();

            $result = $this->crearRegistroKardex(
                inventory: $inventory,
                operation_type: 'Compra',
                operation_id: $purchase->id,
                operation_detail_id: $item->id,
                document_type: 'CCF',
                document_number: $purchase->document_number,
                entity: $provider->comercial_name ?? 'Proveedor',
                nationality: $provider->pais->name ?? 'Salvadoreña',
                quantity: $item->quantity,
                is_input: true,
                unit_price: $item->price,
                date: $purchase->purchase_date,
                recalculate_subsequent: true // Activar recálculo de movimientos posteriores
            );

            return $result;
        });
    }

    /**
     * Registra una venta en el Kardex
     */
    public function registrarVenta(Sale $sale, SaleItem $item): bool
    {
        return DB::transaction(function () use ($sale, $item) {
            $inventory = $item->inventory;

            // Si es producto agrupado, registrar hijos
            if ($inventory->product->is_grouped ?? false) {
                return $this->registrarVentaProductoAgrupado($sale, $item);
            }

            $customer = $sale->customer;

            return $this->crearRegistroKardex(
                inventory: $inventory,
                operation_type: 'Venta',
                operation_id: $sale->id,
                operation_detail_id: $item->id,
                document_type: $sale->documenttype->name ?? 'S/N',
                document_number: $sale->document_internal_number ?? (string) $sale->id,
                entity: $this->formatearEntidad($customer),
                nationality: $customer->country->name ?? 'Salvadoreña',
                quantity: $item->quantity,
                is_input: false,
                unit_price: $item->price,
                date: $sale->operation_date
            );
        });
    }

    /**
     * Registra anulación de venta (devuelve stock)
     */
    public function registrarAnulacionVenta(Sale $sale, SaleItem $item): bool
    {
        return DB::transaction(function () use ($sale, $item) {
            $inventory = $item->inventory;

            // Si es producto agrupado, registrar hijos
            if ($inventory->product->is_grouped ?? false) {
                return $this->registrarAnulacionVentaProductoAgrupado($sale, $item);
            }

            $customer = $sale->customer;
            $documentType = $sale->documenttype->name ?? 'S/N';

            return $this->crearRegistroKardex(
                inventory: $inventory,
                operation_type: 'Anulacion',
                operation_id: $sale->id,
                operation_detail_id: $item->id,
                document_type: 'ANULACION - ' . $documentType,
                document_number: $sale->document_internal_number ?? (string) $sale->id,
                entity: $this->formatearEntidad($customer),
                nationality: $customer->country->name ?? 'Salvadoreña',
                quantity: $item->quantity,
                is_input: true, // Devuelve al stock
                unit_price: $item->price,
                date: now()
            );
        });
    }

    /**
     * Registra nota de crédito de ventas (devuelve stock)
     */
    public function registrarNotaCreditoVenta(CreditNote $creditNote, CreditNoteItem $item): bool
    {
        return DB::transaction(function () use ($creditNote, $item) {
            $inventory = $item->inventory;
            $customer = $creditNote->customer;

            return $this->crearRegistroKardex(
                inventory: $inventory,
                operation_type: 'Nota de Credito',
                operation_id: $creditNote->id,
                operation_detail_id: $item->id,
                document_type: 'NC',
                document_number: $creditNote->document_number ?? 'NC-' . $creditNote->id,
                entity: $this->formatearEntidad($customer),
                nationality: $customer->country->name ?? 'Salvadoreña',
                quantity: $item->quantity,
                is_input: true, // Devuelve al stock
                unit_price: $item->price,
                date: $creditNote->operation_date
            );
        });
    }

    /**
     * Registra nota de crédito de compras (sale del stock)
     */
    public function registrarNotaCreditoCompra(CreditNotePurchase $creditNote, CreditNotePurchaseItem $item): bool
    {
        return DB::transaction(function () use ($creditNote, $item) {
            $inventory = $item->inventory;
            $provider = $creditNote->provider()->with('pais')->first();

            return $this->crearRegistroKardex(
                inventory: $inventory,
                operation_type: 'Nota de Credito Compra',
                operation_id: $creditNote->id,
                operation_detail_id: $item->id,
                document_type: 'NC-COMPRA',
                document_number: $creditNote->document_number ?? 'NCC-' . $creditNote->id,
                entity: $provider->comercial_name ?? 'Proveedor',
                nationality: $provider->pais->name ?? 'Salvadoreña',
                quantity: $item->quantity,
                is_input: false, // Sale del stock
                unit_price: $item->price,
                date: $creditNote->operation_date
            );
        });
    }

    /**
     * Registra traslado (origen o destino)
     */
    public function registrarTraslado(Transfer $transfer, TransferItem $item, bool $esOrigen = true): bool
    {
        return DB::transaction(function () use ($transfer, $item, $esOrigen) {
            $inventory = $esOrigen ? $item->inventory : $item->inventoryDestination;
            $branchOrigen = $transfer->branchOrigin;
            $branchDestino = $transfer->branchDestination;

            $operationType = $esOrigen ? 'Traslado Salida' : 'Traslado Entrada';
            $documentType = $esOrigen ? 'TRASLADO-OUT' : 'TRASLADO-IN';
            $entity = $esOrigen
                ? "Traslado hacia: {$branchDestino->name}"
                : "Traslado desde: {$branchOrigen->name}";

            return $this->crearRegistroKardex(
                inventory: $inventory,
                operation_type: $operationType,
                operation_id: $transfer->id,
                operation_detail_id: $item->id,
                document_type: $documentType,
                document_number: $transfer->document_number ?? 'TRAS-' . $transfer->id,
                entity: $entity,
                nationality: 'Salvadoreña',
                quantity: $item->quantity,
                is_input: !$esOrigen, // Entrada en destino, salida en origen
                unit_price: $inventory->cost_without_taxes,
                date: $transfer->transfer_date
            );
        });
    }

    /**
     * Registra ajuste de inventario (entrada o salida)
     */
    public function registrarAjuste(AdjustmentInventory $adjustment, $item, bool $esEntrada): bool
    {
        return DB::transaction(function () use ($adjustment, $item, $esEntrada) {
            // $item puede ser un objeto con inventory_id o el propio Inventory
            $inventory = is_object($item) && isset($item->inventory)
                ? $item->inventory
                : Inventory::find($item->inventory_id ?? $item);

            $quantity = is_object($item) ? ($item->quantity ?? $item->adjustment_quantity) : 1;
            $price = is_object($item) ? ($item->price ?? $inventory->cost_without_taxes) : $inventory->cost_without_taxes;

            return $this->crearRegistroKardex(
                inventory: $inventory,
                operation_type: $esEntrada ? 'Ajuste Entrada' : 'Ajuste Salida',
                operation_id: $adjustment->id,
                operation_detail_id: is_object($item) ? ($item->id ?? $adjustment->id) : $adjustment->id,
                document_type: $esEntrada ? 'AJUSTE-IN' : 'AJUSTE-OUT',
                document_number: $adjustment->document_number ?? 'AJ-' . $adjustment->id,
                entity: 'Ajuste de Inventario',
                nationality: 'Salvadoreña',
                quantity: $quantity,
                is_input: $esEntrada,
                unit_price: $price,
                date: $adjustment->adjustment_date ?? now()
            );
        });
    }

    /**
     * Registra anulación de compra (sale del stock)
     */
    public function registrarAnulacionCompra(Purchase $purchase, PurchaseItem $item): bool
    {
        return DB::transaction(function () use ($purchase, $item) {
            $inventory = $item->inventory;
            $provider = $purchase->provider()->with('pais')->first();

            return $this->crearRegistroKardex(
                inventory: $inventory,
                operation_type: 'Anulacion',
                operation_id: $purchase->id,
                operation_detail_id: $item->id,
                document_type: 'ANULACION -CCF',
                document_number: $purchase->document_number,
                entity: $provider->comercial_name ?? 'Proveedor',
                nationality: $provider->pais->name ?? 'Salvadoreña',
                quantity: $item->quantity,
                is_input: false, // Sale del stock (anula compra)
                unit_price: $item->price,
                date: now()
            );
        });
    }

    /**
     * MÉTODO PRIVADO: Crea el registro de Kardex con cálculos automáticos
     */
    private function crearRegistroKardex(
        Inventory $inventory,
        string $operation_type,
        int $operation_id,
        int $operation_detail_id,
        string $document_type,
        ?string $document_number,
        string $entity,
        string $nationality,
        int $quantity,
        bool $is_input,
        float $unit_price,
        $date,
        bool $skip_inventory_update = false,
        bool $recalculate_subsequent = false
    ): bool {
        try {
            // Obtener stock anterior (último movimiento ANTES de esta fecha)
            $previous_stock = $this->obtenerSaldoAnteriorPorFecha($inventory, $date);

            // Calcular movimientos
            $stock_in = $is_input ? $quantity : 0;
            $stock_out = $is_input ? 0 : $quantity;
            $stock_actual = $is_input
                ? $previous_stock + $quantity
                : $previous_stock - $quantity;

            // Validar stock suficiente para salidas
            if (!$is_input && $stock_actual < 0) {
                Log::error("Stock insuficiente en Kardex", [
                    'inventory_id' => $inventory->id,
                    'product' => $inventory->product->name,
                    'stock_actual' => $previous_stock,
                    'cantidad_solicitada' => $quantity
                ]);
                throw new \Exception("Stock insuficiente para {$inventory->product->name}. Stock actual: {$previous_stock}, solicitado: {$quantity}");
            }

            // Actualizar inventario si no se debe saltar
            if (!$skip_inventory_update) {
                if ($is_input) {
                    $inventory->increment('stock', $quantity);
                } else {
                    $inventory->decrement('stock', $quantity);
                }
                $inventory->refresh();
            }

            // Calcular dinero
            $money_in = $is_input ? ($quantity * $unit_price) : 0;
            $money_out = $is_input ? 0 : ($quantity * $unit_price);
            $money_actual = $stock_actual * $unit_price;

            // Calcular costo promedio
            $promedial_cost = $this->calcularCostoPromedio(
                $inventory,
                $unit_price,
                $quantity,
                $is_input
            );

            // Crear registro Kardex
            $kardex = Kardex::create([
                'branch_id' => $inventory->branch_id,
                'date' => $date instanceof Carbon ? $date : Carbon::parse($date),
                'operation_type' => $operation_type,
                'operation_id' => $operation_id,
                'operation_detail_id' => $operation_detail_id,
                'document_type' => $document_type,
                'document_number' => $document_number,
                'entity' => $entity,
                'nationality' => $nationality,
                'inventory_id' => $inventory->id,
                'previous_stock' => $previous_stock,
                'stock_in' => $stock_in,
                'stock_out' => $stock_out,
                'stock_actual' => $stock_actual,
                'money_in' => round($money_in, 2),
                'money_out' => round($money_out, 2),
                'money_actual' => round($money_actual, 2),
                'sale_price' => $is_input ? 0 : $unit_price,
                'purchase_price' => $is_input ? $unit_price : 0,
                'promedial_cost' => round($promedial_cost, 2)
            ]);

            // Validar integridad del registro
            $this->validarIntegridadKardex($kardex);

            // Recalcular movimientos posteriores si se solicita
            if ($recalculate_subsequent) {
                $this->recalcularMovimientosPosteriores($kardex);
            }

            return (bool) $kardex;

        } catch (\Exception $e) {
            Log::error("Error al crear registro Kardex", [
                'inventory_id' => $inventory->id,
                'operation_type' => $operation_type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calcula costo promedio ponderado (CORREGIDO)
     */
    private function calcularCostoPromedio(
        Inventory $inventory,
        float $nuevoCosto,
        int $cantidad,
        bool $is_input
    ): float {
        // Obtener el ÚLTIMO Kardex de este inventory específico
        $ultimoKardex = Kardex::where('inventory_id', $inventory->id)
            ->orderByDesc('id')
            ->first();

        // Para salidas, mantener el costo promedio actual
        if (!$is_input) {
            return $ultimoKardex?->promedial_cost ?? $inventory->cost_without_taxes;
        }

        // Para entradas, calcular nuevo promedio ponderado
        $costoAnterior = $ultimoKardex?->promedial_cost ?? $inventory->cost_without_taxes;
        $stockAnterior = $ultimoKardex?->stock_actual ?? 0;

        $totalCantidad = $stockAnterior + $cantidad;

        if ($totalCantidad > 0) {
            $promedial = (($stockAnterior * $costoAnterior) + ($cantidad * $nuevoCosto)) / $totalCantidad;
            return $promedial;
        }

        return $nuevoCosto;
    }

    /**
     * Valida integridad del registro Kardex
     */
    private function validarIntegridadKardex(Kardex $kardex): void
    {
        $calculado = $kardex->previous_stock + $kardex->stock_in - $kardex->stock_out;

        if ($kardex->stock_actual !== $calculado) {
            Log::error("Error de integridad en Kardex", [
                'kardex_id' => $kardex->id,
                'inventory_id' => $kardex->inventory_id,
                'esperado' => $calculado,
                'actual' => $kardex->stock_actual,
                'previous_stock' => $kardex->previous_stock,
                'stock_in' => $kardex->stock_in,
                'stock_out' => $kardex->stock_out
            ]);

            throw new \Exception("Error de integridad en registro de Kardex #{$kardex->id}. Stock calculado: {$calculado}, Stock registrado: {$kardex->stock_actual}");
        }
    }

    /**
     * Recalcula TODOS los movimientos posteriores a una fecha determinada
     *
     * Este método se ejecuta cuando:
     * - Se registra una compra con fecha anterior a movimientos existentes
     * - Se corrige un movimiento histórico
     * - Se anula una operación con fecha anterior
     *
     * Algoritmo:
     * 1. Obtener todos los movimientos POSTERIORES ordenados por fecha ASC
     * 2. Para cada movimiento:
     *    - Recalcular previous_stock (saldo del movimiento anterior)
     *    - Recalcular stock_actual basado en su entrada/salida
     *    - Recalcular money_actual y costo promedio
     *    - Actualizar registro en BD
     *
     * @param Kardex $kardexInsertado El movimiento recién insertado
     * @return int Número de movimientos actualizados
     */
    private function recalcularMovimientosPosteriores(Kardex $kardexInsertado): int
    {
        try {
            Log::info("Iniciando recálculo de movimientos posteriores", [
                'kardex_id' => $kardexInsertado->id,
                'inventory_id' => $kardexInsertado->inventory_id,
                'fecha' => $kardexInsertado->date
            ]);

            // 1. Obtener TODOS los movimientos POSTERIORES a la fecha del movimiento insertado
            // Ordenados por fecha ASC, luego por ID (para mantener orden de creación)
            $movimientosPosteriores = Kardex::where('inventory_id', $kardexInsertado->inventory_id)
                ->where(function ($query) use ($kardexInsertado) {
                    $query->where('date', '>', $kardexInsertado->date)
                          ->orWhere(function ($q) use ($kardexInsertado) {
                              // Si es la misma fecha, solo tomar los que se crearon después
                              $q->where('date', '=', $kardexInsertado->date)
                                ->where('id', '>', $kardexInsertado->id);
                          });
                })
                ->orderBy('date', 'ASC')
                ->orderBy('id', 'ASC')
                ->get();

            if ($movimientosPosteriores->isEmpty()) {
                Log::info("No hay movimientos posteriores para recalcular");
                return 0;
            }

            Log::info("Movimientos a recalcular: {$movimientosPosteriores->count()}");

            // 2. Obtener el saldo inicial para empezar el recálculo
            // El saldo inicial es el stock_actual del movimiento recién insertado
            $saldoAnterior = $kardexInsertado->stock_actual;
            $costoPromedioAnterior = $kardexInsertado->promedial_cost;

            $movimientosActualizados = 0;

            // 3. Recorrer cada movimiento posterior y recalcular
            foreach ($movimientosPosteriores as $movimiento) {
                // Guardar valores originales para log
                $stockOriginal = $movimiento->stock_actual;

                // RECALCULAR: previous_stock es el saldo del movimiento anterior
                $movimiento->previous_stock = $saldoAnterior;

                // RECALCULAR: stock_actual basado en entrada/salida
                $movimiento->stock_actual = $saldoAnterior + $movimiento->stock_in - $movimiento->stock_out;

                // RECALCULAR: Costo promedio ponderado
                if ($movimiento->stock_in > 0) {
                    // Es una entrada: recalcular costo promedio ponderado
                    $nuevoCosto = $movimiento->purchase_price;
                    $totalCantidad = $saldoAnterior + $movimiento->stock_in;

                    if ($totalCantidad > 0) {
                        $costoPromedioAnterior = (($saldoAnterior * $costoPromedioAnterior) + ($movimiento->stock_in * $nuevoCosto)) / $totalCantidad;
                    }
                } else {
                    // Es una salida: mantener el costo promedio anterior
                    // No se modifica $costoPromedioAnterior
                }

                $movimiento->promedial_cost = round($costoPromedioAnterior, 2);

                // RECALCULAR: Valores monetarios
                $movimiento->money_actual = round($movimiento->stock_actual * $movimiento->promedial_cost, 2);

                // Guardar cambios
                $movimiento->save();

                Log::info("Movimiento recalculado", [
                    'kardex_id' => $movimiento->id,
                    'fecha' => $movimiento->date,
                    'tipo' => $movimiento->operation_type,
                    'stock_anterior_original' => $stockOriginal,
                    'stock_anterior_nuevo' => $movimiento->stock_actual,
                    'saldo_base' => $saldoAnterior
                ]);

                // Actualizar saldo anterior para el siguiente movimiento
                $saldoAnterior = $movimiento->stock_actual;
                $movimientosActualizados++;
            }

            Log::info("Recálculo completado exitosamente", [
                'movimientos_actualizados' => $movimientosActualizados
            ]);

            return $movimientosActualizados;

        } catch (\Exception $e) {
            Log::error("Error al recalcular movimientos posteriores", [
                'kardex_id' => $kardexInsertado->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Error al recalcular movimientos posteriores: {$e->getMessage()}");
        }
    }

    /**
     * Obtiene el saldo anterior basado en la fecha del movimiento
     * Busca el último movimiento ANTES O DEL MISMO DÍA (que ya existe en BD)
     *
     * IMPORTANTE: Este método se ejecuta ANTES de insertar el nuevo registro,
     * por lo tanto, cualquier movimiento del mismo día que encuentre
     * YA ESTÁ en la base de datos y debe usarse como saldo anterior.
     *
     * Casos manejados:
     * 1. Movimientos de días anteriores: usa el último encontrado
     * 2. Movimientos del mismo día: usa el último insertado (mayor ID)
     * 3. Sin movimientos previos: retorna stock actual del inventario
     *
     * @param Inventory $inventory
     * @param mixed $date Fecha del movimiento (Carbon o string)
     * @return int Saldo anterior (stock actual del último movimiento encontrado)
     */
    private function obtenerSaldoAnteriorPorFecha(Inventory $inventory, $date): int
    {
        $fecha = $date instanceof Carbon ? $date : Carbon::parse($date);

        // Buscar el último movimiento ANTES O DEL MISMO DÍA de esta fecha
        // El orderBy('id', 'DESC') garantiza que si hay varios del mismo día,
        // tome el último insertado (el de mayor ID)
        $ultimoMovimiento = Kardex::where('inventory_id', $inventory->id)
            ->where('date', '<=', $fecha)  // ✅ Cambiado de '<' a '<='
            ->orderBy('date', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        // Si no hay movimientos anteriores, retornar el stock actual del inventario
        // (esto puede ocurrir si es el primer movimiento o si no hay movimientos anteriores a esta fecha)
        if (!$ultimoMovimiento) {
            // Si no hay movimientos previos, el stock anterior es 0 (a menos que sea inventario inicial)
            return $inventory->stock ?? 0;
        }

        Log::info("Saldo anterior encontrado", [
            'inventory_id' => $inventory->id,
            'fecha_buscada' => $fecha->format('Y-m-d'),
            'kardex_encontrado_id' => $ultimoMovimiento->id,
            'kardex_encontrado_fecha' => $ultimoMovimiento->date,
            'saldo_anterior' => $ultimoMovimiento->stock_actual
        ]);

        return $ultimoMovimiento->stock_actual;
    }

    /**
     * Formatea entidad (cliente/proveedor)
     */
    private function formatearEntidad($entity): string
    {
        if (!$entity) {
            return 'Varios';
        }

        $nombre = $entity->name ?? $entity->comercial_name ?? 'Varios';
        $apellido = $entity->last_name ?? '';

        return trim("{$nombre} {$apellido}");
    }

    /**
     * Registra venta de producto agrupado (procesa hijos)
     */
    private function registrarVentaProductoAgrupado(Sale $sale, SaleItem $item): bool
    {
        $inventoriesGrouped = InventoryGrouped::with('inventoryChild.product')
            ->where('inventory_grouped_id', $item->inventory_id)
            ->get();

        foreach ($inventoriesGrouped as $groupedItem) {
            $childInventory = $groupedItem->inventoryChild;
            $childQuantity = $groupedItem->quantity * $item->quantity;

            $customer = $sale->customer;

            $this->crearRegistroKardex(
                inventory: $childInventory,
                operation_type: 'Venta',
                operation_id: $sale->id,
                operation_detail_id: $item->id,
                document_type: $sale->documenttype->name ?? 'S/N',
                document_number: $sale->document_internal_number ?? (string) $sale->id,
                entity: $this->formatearEntidad($customer),
                nationality: $customer->country->name ?? 'Salvadoreña',
                quantity: $childQuantity,
                is_input: false,
                unit_price: $childInventory->cost_without_taxes,
                date: $sale->operation_date
            );
        }

        return true;
    }

    /**
     * Registra anulación de venta de producto agrupado
     */
    private function registrarAnulacionVentaProductoAgrupado(Sale $sale, SaleItem $item): bool
    {
        $inventoriesGrouped = InventoryGrouped::with('inventoryChild.product')
            ->where('inventory_grouped_id', $item->inventory_id)
            ->get();

        foreach ($inventoriesGrouped as $groupedItem) {
            $childInventory = $groupedItem->inventoryChild;
            $childQuantity = $groupedItem->quantity * $item->quantity;

            $customer = $sale->customer;
            $documentType = $sale->documenttype->name ?? 'S/N';

            $this->crearRegistroKardex(
                inventory: $childInventory,
                operation_type: 'Anulacion',
                operation_id: $sale->id,
                operation_detail_id: $item->id,
                document_type: 'ANULACION - ' . $documentType,
                document_number: $sale->document_internal_number ?? (string) $sale->id,
                entity: $this->formatearEntidad($customer),
                nationality: $customer->country->name ?? 'Salvadoreña',
                quantity: $childQuantity,
                is_input: true, // Devuelve al stock
                unit_price: $childInventory->cost_without_taxes,
                date: now()
            );
        }

        return true;
    }

    /**
     * Elimina todos los registros de Kardex asociados a una compra
     * Utilizado cuando se necesita ajustar una compra finalizada
     *
     * @param Purchase $purchase
     * @return int Número de registros eliminados
     */
    public function eliminarKardexCompra(Purchase $purchase): int
    {
        return DB::transaction(function () use ($purchase) {
            Log::info("Eliminando kardex de compra", [
                'purchase_id' => $purchase->id,
                'document_number' => $purchase->document_number
            ]);

            // Obtener todos los items de la compra
            $purchaseItems = PurchaseItem::where('purchase_id', $purchase->id)->get();
            $registrosEliminados = 0;

            foreach ($purchaseItems as $item) {
                // Buscar y eliminar registros de kardex de esta compra
                $kardexRecords = Kardex::where('operation_type', 'Compra')
                    ->where('operation_id', $purchase->id)
                    ->where('operation_detail_id', $item->id)
                    ->get();

                foreach ($kardexRecords as $kardex) {
                    // Antes de eliminar, ajustar el stock del inventario
                    $inventory = $kardex->inventory;

                    // Como era una entrada (compra), al eliminar debemos restar del stock
                    if ($kardex->stock_in > 0) {
                        $inventory->decrement('stock', $kardex->stock_in);
                    }

                    Log::info("Eliminando registro kardex", [
                        'kardex_id' => $kardex->id,
                        'inventory_id' => $kardex->inventory_id,
                        'fecha' => $kardex->date,
                        'cantidad' => $kardex->stock_in
                    ]);

                    $kardex->delete();
                    $registrosEliminados++;
                }
            }

            Log::info("Kardex de compra eliminado", [
                'purchase_id' => $purchase->id,
                'registros_eliminados' => $registrosEliminados
            ]);

            return $registrosEliminados;
        });
    }

    /**
     * Ajusta una compra existente: regenera el kardex con nuevos datos
     * y recalcula todos los movimientos posteriores
     *
     * @param Purchase $purchase Compra con datos ya actualizados
     * @return bool
     */
    public function ajustarCompra(Purchase $purchase): bool
    {
        return DB::transaction(function () use ($purchase) {
            Log::info("Ajustando compra", [
                'purchase_id' => $purchase->id,
                'purchase_date' => $purchase->purchase_date
            ]);

            // Obtener todos los items de la compra
            $purchaseItems = PurchaseItem::where('purchase_id', $purchase->id)->get();

            if ($purchaseItems->isEmpty()) {
                throw new \Exception("No se encontraron items para la compra #{$purchase->id}");
            }

            // Registrar nuevo kardex para cada item (con recálculo automático de posteriores)
            foreach ($purchaseItems as $item) {
                $this->registrarCompra($purchase, $item);
            }

            Log::info("Compra ajustada exitosamente", [
                'purchase_id' => $purchase->id,
                'items_procesados' => $purchaseItems->count()
            ]);

            return true;
        });
    }

    /**
     * Ajusta el inventario inicial de un producto
     * Elimina el kardex inicial anterior, crea uno nuevo y recalcula posteriores
     *
     * @param Kardex $kardexInicial Registro de kardex tipo "INVENTARIO INICIAL"
     * @param array $data Datos del ajuste: stock_inicial, costo_inicial, motivo
     * @return bool
     */
    public function ajustarInventarioInicial(Kardex $kardexInicial, array $data): bool
    {
        return DB::transaction(function () use ($kardexInicial, $data) {
            // Validar que sea un inventario inicial
            if ($kardexInicial->operation_type !== 'INVENTARIO INICIAL') {
                throw new \Exception("El kardex #{$kardexInicial->id} no es de tipo INVENTARIO INICIAL");
            }

            Log::info("Ajustando inventario inicial", [
                'kardex_id' => $kardexInicial->id,
                'inventory_id' => $kardexInicial->inventory_id,
                'stock_anterior' => $kardexInicial->stock_actual,
                'stock_nuevo' => $data['stock_inicial'],
                'costo_anterior' => $kardexInicial->purchase_price,
                'costo_nuevo' => $data['costo_inicial'],
                'motivo' => $data['motivo']
            ]);

            // Guardar datos necesarios antes de eliminar
            $inventory = $kardexInicial->inventory;
            $inventoryId = $kardexInicial->inventory_id;
            $fecha = $kardexInicial->date;
            $stockAnterior = $kardexInicial->stock_actual;
            $stockNuevo = $data['stock_inicial'];
            $costoNuevo = $data['costo_inicial'];

            // Calcular diferencia de stock para ajustar el inventario
            $diferencia = $stockNuevo - $stockAnterior;

            // Eliminar el kardex inicial anterior
            $kardexInicial->delete();
            Log::info("Kardex inicial eliminado", ['kardex_id' => $kardexInicial->id]);

            // Ajustar el stock en la tabla inventories
            if ($diferencia > 0) {
                $inventory->increment('stock', $diferencia);
            } elseif ($diferencia < 0) {
                $inventory->decrement('stock', abs($diferencia));
            }
            $inventory->refresh();

            // Crear nuevo kardex inicial con los valores ajustados
            $nuevoKardex = Kardex::create([
                'branch_id' => $inventory->branch_id,
                'date' => $fecha,
                'operation_type' => 'INVENTARIO INICIAL',
                'operation_id' => $inventory->id,
                'operation_detail_id' => $inventory->id,
                'document_type' => 'INVENTARIO INICIAL',
                'document_number' => 'INV-' . $inventory->id . '-AJUSTADO',
                'entity' => 'Sistema (Ajuste: ' . substr($data['motivo'], 0, 30) . '...)',
                'nationality' => 'Salvadoreña',
                'inventory_id' => $inventoryId,
                'previous_stock' => 0,
                'stock_in' => $stockNuevo,
                'stock_out' => 0,
                'stock_actual' => $stockNuevo,
                'money_in' => round($stockNuevo * $costoNuevo, 2),
                'money_out' => 0,
                'money_actual' => round($stockNuevo * $costoNuevo, 2),
                'sale_price' => 0,
                'purchase_price' => $costoNuevo,
                'promedial_cost' => round($costoNuevo, 2)
            ]);

            Log::info("Nuevo kardex inicial creado", [
                'kardex_id' => $nuevoKardex->id,
                'stock_actual' => $nuevoKardex->stock_actual,
                'promedial_cost' => $nuevoKardex->promedial_cost
            ]);

            // Recalcular TODOS los movimientos posteriores
            $movimientosActualizados = $this->recalcularMovimientosPosteriores($nuevoKardex);

            Log::info("Ajuste de inventario inicial completado", [
                'inventory_id' => $inventoryId,
                'movimientos_recalculados' => $movimientosActualizados,
                'stock_final_inventories' => $inventory->stock
            ]);

            return true;
        });
    }
}
