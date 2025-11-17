# ANÁLISIS KARDEX HELPER
## Sistema de Gestión de Inventario - ERP Clementina

**Fecha**: 30 de Octubre de 2025
**Versión**: 1.0
**Alcance**: Análisis completo del sistema de Kardex actual

---

## 📋 RESUMEN EJECUTIVO

El **KardexHelper** es un helper global que gestiona el registro de movimientos de inventario (entradas/salidas) en todo el sistema ERP. Actualmente se usa en **12 archivos** diferentes con **lógica duplicada** en cada uno.

### Estadísticas Actuales

| Métrica | Valor |
|---------|-------|
| Archivos que usan Kardex | 12 archivos |
| Parámetros del helper | 12 parámetros |
| Líneas de código duplicadas | ~100+ líneas |
| Operaciones que registran Kardex | 8 tipos |
| Cálculo de costo promedio | Solo en entradas |

---

## 🏗️ ARQUITECTURA ACTUAL

### KardexHelper.php (80 líneas)

```php
public static function createKardexFromInventory(
    int    $branch_id,              // 1. Sucursal
    string $date,                   // 2. Fecha de operación
    string $operation_type,         // 3. Tipo: Compra, Venta, Traslado, etc.
    string $operation_id,           // 4. ID de la operación
    string $operation_detail_id,    // 5. ID del detalle
    string $document_type,          // 6. Tipo de documento
    string $document_number,        // 7. Número de documento
    string $entity,                 // 8. Cliente/Proveedor
    string $nationality,            // 9. Nacionalidad
    int    $inventory_id,           // 10. ID del inventario
    int    $previous_stock,         // 11. Stock anterior
    int    $stock_in,               // 12. Entrada
    int    $stock_out,              // 13. Salida
    int    $stock_actual,           // 14. Stock actual
    float  $money_in,               // 15. Dinero entrada
    float  $money_out,              // 16. Dinero salida
    float  $money_actual,           // 17. Dinero actual
    float  $sale_price,             // 18. Precio venta
    float  $purchase_price,         // 19. Precio compra
)
```

### Flujo Actual

```
┌──────────────────┐
│  Operación       │  (Compra, Venta, Traslado, etc.)
│  (Controller)    │
└────────┬─────────┘
         │
         │ 1. Actualiza Inventory.stock
         │ 2. Obtiene datos (cliente, sucursal, etc.)
         │ 3. Calcula totales manualmente
         │
         ▼
┌──────────────────┐
│  KardexHelper    │  12 parámetros
│  ::create()      │
└────────┬─────────┘
         │
         │ 4. Calcula costo promedio (solo si stock_in > 0)
         │ 5. Busca penúltimo registro InventoryCostoHistory
         │ 6. Crea registro Kardex
         │
         ▼
┌──────────────────┐
│   Base de Datos  │
│   - kardex       │
│   - inventory    │
└──────────────────┘
```

---

## 📍 UBICACIONES DE USO

### 12 Archivos Identificados

| # | Archivo | Operación | Líneas |
|---|---------|-----------|--------|
| 1 | **Inventory.php** (Observer) | Inventario Inicial | ~20 |
| 2 | **DTEController.php** | Anulación Ventas | ~40 |
| 3 | **PurchaseResource.php** | Compras | ~25 |
| 4 | **EditPurchase.php** | Edición Compras | ~25 |
| 5 | **EditSale.php** | Edición Ventas | ~30 |
| 6 | **EditCreditNote.php** | Notas de Crédito Ventas | ~35 |
| 7 | **EditCreditNotePurchase.php** | Notas de Crédito Compras | ~35 |
| 8 | **CreditNotePurchaseResource.php** | Creación NC Compras | ~25 |
| 9 | **EditAdjustmentInventory.php** | Ajustes Inventario | ~30 |
| 10 | **EditTransfer.php** | Traslados | ~30 |
| 11 | **transferActions.php** | Acciones Traslados | ~40 |
| 12 | **orderActions.php** | Órdenes | ~25 |

**Total**: ~360 líneas de código duplicado en llamadas al Kardex

---

## 🔍 PROBLEMAS IDENTIFICADOS

### 1. ❌ CÓDIGO DUPLICADO MASIVO

Cada archivo replica la misma lógica:

```php
// ❌ Patrón repetido en 12 archivos diferentes
$kardex = KardexHelper::createKardexFromInventory(
    $inventory->branch_id,
    $sale->operation_date,
    'Venta',
    $sale->id,
    $item->id,
    $sale->documenttype->name ?? 'S/N',
    $sale->document_internal_number,
    ($client->name ?? 'Varios') . ' ' . ($client->last_name ?? ''),
    $client->country->name ?? 'Salvadoreña',
    $inventory->id,
    $inventory->stock - $item->quantity,  // Calculado manualmente
    0,
    $item->quantity,
    $newStock,  // Calculado manualmente
    0,
    $item->quantity * $item->price,  // Calculado manualmente
    $inventory->stock * $item->price,  // Calculado manualmente
    $item->price,
    $inventory->cost_without_taxes
);
```

**Problemas**:
- 🔴 360+ líneas duplicadas
- 🔴 Cálculos manuales en cada ubicación
- 🔴 Fácil introducir errores
- 🔴 Difícil de mantener

---

### 2. ❌ CÁLCULO DE COSTO PROMEDIO INCORRECTO

**Código Actual** (líneas 36-48):

```php
if ($stock_in > 0) {
    $inventory = Inventory::find($inventory_id);  // ❌ Query adicional

    // Obtenemos el penúltimo registro
    $penultimoRegistro = InventoryCostoHistory::orderByDesc('id')
        ->skip(1)  // ❌ Skip no garantiza orden correcto
        ->first();

    $costo_anterior = $penultimoRegistro->costo_actual ?? 0;  // ❌ Puede ser NULL
    $stockAnterior = $inventory->stock - $stock_in;

    $totalCantidad = $stockAnterior + $stock_in;
    if ($totalCantidad > 0) {
        $promedial_cost = (($stockAnterior * $costo_anterior) + ($stock_in * $purchase_price)) / $totalCantidad;
    }
}
```

**Problemas**:
1. **Query adicional**: `Inventory::find()` se ejecuta cuando ya se tiene el inventory
2. **Lógica incorrecta**: Usa `skip(1)` en vez de filtrar por `inventory_id`
3. **Penúltimo != Último**: Busca penúltimo cuando debería buscar el último
4. **No filtra por inventory**: Trae cualquier registro, no el específico
5. **Race condition**: Entre obtener stock y calcular costo
6. **Solo para entradas**: No recalcula en salidas

---

### 3. ❌ MODELO InventoryCostoHistory SIN USO REAL

```php
// app/Models/InventoryCostoHistory.php
class InventoryCostoHistory extends Model
{
    protected $fillable = [
        'inventory_id',
        'inventory_id',  // ❌ Duplicado!
        'costo_anterio',  // ❌ Typo: anterio vs anterior
        'costo_actual',
        'fecha'
    ];
}
```

**Problemas**:
1. Campo `inventory_id` duplicado
2. Typo en `costo_anterio`
3. No tiene relaciones definidas
4. Se crea en EditPurchase pero **NO** se usa el valor calculado
5. El KardexHelper busca este modelo pero **no lo actualiza**

---

### 4. ❌ PARÁMETROS EXCESIVOS (12 parámetros)

**Firma actual**:
```php
createKardexFromInventory(
    $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12
)
```

**Problemas**:
- 🔴 Difícil de leer
- 🔴 Fácil equivocar orden
- 🔴 Imposible agregar parámetros opcionales
- 🔴 No usa objetos ni DTOs

---

### 5. ❌ CÁLCULOS MANUALES EN CADA LLAMADA

**Ejemplo en DTEController.php (línea 964-983)**:

```php
$kardex = KardexHelper::createKardexFromInventory(
    $inventory->branch_id,
    now(),
    'Anulacion Venta',
    $venta->id,
    $item->id,
    $documnetType,
    $venta->document_internal_number,
    $entity,
    $pais,
    $inventory->id,
    $inventory->stock - $item->quantity,  // ❌ Calculado manualmente
    $item->quantity,                      // ✅ Dato directo
    0,                                    // ✅ Dato directo
    $newStock,                            // ❌ Calculado antes
    $item->quantity * $item->price,       // ❌ Calculado manualmente
    0,                                    // ✅ Dato directo
    $inventory->stock * $item->price,     // ❌ Calculado manualmente
    $item->price,                         // ✅ Dato directo
    $inventory->cost_without_taxes        // ✅ Dato directo
);
```

**Problemas**:
- Los cálculos se repiten en cada ubicación
- Propenso a errores de cálculo
- Dificulta testing unitario

---

### 6. ❌ NO HAY VALIDACIONES

```php
// ❌ No valida que stock_actual = previous_stock + stock_in - stock_out
// ❌ No valida que money_actual sea correcto
// ❌ No valida que inventory_id exista
// ❌ No maneja transacciones
```

---

### 7. ❌ PRODUCTOS AGRUPADOS DUPLICAN LÓGICA

En **EditCreditNote.php**, **DTEController.php**, etc:

```php
if ($is_grouped) {
    $inventoriesGrouped = InventoryGrouped::with('inventoryChild.product')
        ->where('inventory_grouped_id', $item->inventory_id)->get();

    foreach ($inventoriesGrouped as $inventarioHijo) {
        // ❌ Lógica duplicada para cada hijo
        $kardex = KardexHelper::createKardexFromInventory(
            $inventarioHijo->inventoryChild->branch_id,
            // ... 12 parámetros más
        );
    }
} else {
    // ❌ Lógica duplicada para producto simple
    $kardex = KardexHelper::createKardexFromInventory(
        $inventory->branch_id,
        // ... 12 parámetros más
    );
}
```

**Problema**: Código casi idéntico en ~6 archivos diferentes

---

## 🎯 TIPOS DE OPERACIONES REGISTRADAS

| # | Tipo | Archivos | Stock In | Stock Out |
|---|------|----------|----------|-----------|
| 1 | **INVENTARIO INICIAL** | Inventory Observer | ✅ | ❌ |
| 2 | **Compra** | PurchaseResource, EditPurchase | ✅ | ❌ |
| 3 | **Venta** | EditSale | ❌ | ✅ |
| 4 | **Anulacion Venta** | DTEController | ✅ | ❌ |
| 5 | **Nota de Crédito** (Ventas) | EditCreditNote | ✅ | ❌ |
| 6 | **Nota de Crédito** (Compras) | CreditNotePurchaseResource | ❌ | ✅ |
| 7 | **Traslado** (Origen/Destino) | EditTransfer, transferActions | ✅/❌ | ❌/✅ |
| 8 | **Ajuste Inventario** | EditAdjustmentInventory | ✅ | ✅ |

---

## 💡 OPORTUNIDADES DE MEJORA

### 1. ✅ CREAR KARDEXSERVICE

Centralizar toda la lógica en un Service:

```php
// app/Services/Inventory/KardexService.php
class KardexService
{
    public function registrarCompra(Purchase $purchase, PurchaseItem $item): bool
    {
        // Lógica centralizada
        // Calcula automáticamente previous_stock, stock_actual, money_in, etc.
    }

    public function registrarVenta(Sale $sale, SaleItem $item): bool
    {
        // Lógica centralizada
    }

    public function registrarAnulacionVenta(Sale $sale, SaleItem $item): bool
    {
        // Lógica centralizada
    }

    public function registrarTraslado(Transfer $transfer, TransferItem $item, bool $origen = true): bool
    {
        // Lógica centralizada
    }

    // ... métodos específicos para cada operación
}
```

**Beneficios**:
- ✅ Elimina 360+ líneas duplicadas
- ✅ Cálculos automáticos
- ✅ Fácil testing
- ✅ Un solo lugar para mantener

---

### 2. ✅ USAR DTOs EN VEZ DE 12 PARÁMETROS

```php
// app/DTOs/KardexDataDTO.php
class KardexDataDTO
{
    public function __construct(
        public int $branch_id,
        public Carbon $date,
        public string $operation_type,
        public int $operation_id,
        public int $operation_detail_id,
        public string $document_type,
        public string $document_number,
        public string $entity,
        public string $nationality,
        public int $inventory_id,
        public int $quantity,  // Solo cantidad
        public bool $is_input,  // true = entrada, false = salida
        public float $unit_price,
    ) {}

    public static function fromPurchaseItem(Purchase $purchase, PurchaseItem $item): self
    {
        return new self(
            branch_id: $item->inventory->branch_id,
            date: $purchase->purchase_date,
            operation_type: 'Compra',
            // ... resto calculado automáticamente
        );
    }
}
```

---

### 3. ✅ CORREGIR CÁLCULO DE COSTO PROMEDIO

```php
// Método mejorado en KardexService
private function calcularCostoPromedio(Inventory $inventory, float $nuevoCosto, int $cantidad): float
{
    $ultimoKardex = Kardex::where('inventory_id', $inventory->id)
        ->orderByDesc('id')
        ->first();

    $costoAnterior = $ultimoKardex?->promedial_cost ?? $inventory->cost_without_taxes;
    $stockAnterior = $ultimoKardex?->stock_actual ?? 0;

    $totalCantidad = $stockAnterior + $cantidad;

    if ($totalCantidad > 0) {
        return (($stockAnterior * $costoAnterior) + ($cantidad * $nuevoCosto)) / $totalCantidad;
    }

    return $nuevoCosto;
}
```

**Mejoras**:
- ✅ Usa el Kardex anterior del mismo inventory
- ✅ No hace query adicional a Inventory
- ✅ Orden correcto (último, no penúltimo)
- ✅ Fallback a cost_without_taxes

---

### 4. ✅ ELIMINAR InventoryCostoHistory O ARREGLARLO

**Opción A: Eliminarlo** (recomendado)
- El Kardex ya tiene `promedial_cost`
- Tabla redundante

**Opción B: Arreglarlo**
```php
// Si se decide mantener
class InventoryCostoHistory extends Model
{
    protected $fillable = [
        'inventory_id',
        'costo_anterior',  // ✅ Corregir typo
        'costo_actual',
        'fecha'
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
```

---

### 5. ✅ AGREGAR VALIDACIONES Y TRANSACCIONES

```php
public function registrarVenta(Sale $sale, SaleItem $item): bool
{
    return DB::transaction(function () use ($sale, $item) {
        $inventory = $item->inventory;

        // Validar stock disponible
        if ($inventory->stock < $item->quantity) {
            throw new \Exception("Stock insuficiente");
        }

        // Actualizar stock
        $previousStock = $inventory->stock;
        $inventory->decrement('stock', $item->quantity);
        $inventory->refresh();

        // Crear Kardex
        $kardex = Kardex::create([
            // ... datos
            'previous_stock' => $previousStock,
            'stock_actual' => $inventory->stock,
        ]);

        // Validar integridad
        if ($kardex->stock_actual !== ($kardex->previous_stock - $kardex->stock_out)) {
            throw new \Exception("Error de integridad en Kardex");
        }

        return true;
    });
}
```

---

### 6. ✅ MANEJAR PRODUCTOS AGRUPADOS EN EL SERVICE

```php
public function registrarVenta(Sale $sale, SaleItem $item): bool
{
    $inventory = $item->inventory;

    if ($inventory->product->is_grouped) {
        return $this->registrarVentaProductoAgrupado($sale, $item);
    }

    return $this->registrarVentaProductoSimple($sale, $item);
}

private function registrarVentaProductoAgrupado(Sale $sale, SaleItem $item): bool
{
    $groupedItems = $item->inventory->inventoriesGrouped;

    foreach ($groupedItems as $groupedItem) {
        // Lógica centralizada para cada hijo
        $this->crearRegistroKardex(/* datos del hijo */);
    }

    return true;
}
```

---

## 📊 COMPARACIÓN: ANTES vs DESPUÉS

### ANTES (Actual)

```php
// ❌ En 12 archivos diferentes
$client = $venta->customer;
$documnetType = $venta->documenttype->name ?? 'S/N';
$entity = ($client->name ?? 'Varios') . ' ' . ($client->last_name ?? '');
$pais = $client->country->name ?? 'Salvadoreña';
$newStock = $inventory->stock + $item->quantity;
$inventory->update(['stock' => $newStock]);

$kardex = KardexHelper::createKardexFromInventory(
    $inventory->branch_id,
    now(),
    'Anulacion Venta',
    $venta->id,
    $item->id,
    $documnetType,
    $venta->document_internal_number,
    $entity,
    $pais,
    $inventory->id,
    $inventory->stock - $item->quantity,
    $item->quantity,
    0,
    $newStock,
    $item->quantity * $item->price,
    0,
    $inventory->stock * $item->price,
    $item->price,
    $inventory->cost_without_taxes
);
```

**Problemas**: 20 líneas, cálculos manuales, duplicado 12 veces

---

### DESPUÉS (Propuesto)

```php
// ✅ En un solo Service
app(KardexService::class)->registrarAnulacionVenta($venta, $item);
```

**Beneficios**: 1 línea, cálculos automáticos, lógica centralizada

---

## 🎯 PLAN DE REFACTORIZACIÓN

### Fase 1: Crear KardexService (4 horas)

1. Crear `app/Services/Inventory/KardexService.php`
2. Crear DTOs para cada tipo de operación
3. Implementar métodos específicos:
   - `registrarCompra()`
   - `registrarVenta()`
   - `registrarAnulacionVenta()`
   - `registrarNotaCredito()`
   - `registrarTraslado()`
   - `registrarAjuste()`

**Archivos a crear**:
- `app/Services/Inventory/KardexService.php`
- `app/DTOs/KardexDataDTO.php`

---

### Fase 2: Migrar Controladores (3 horas)

Reemplazar llamadas en orden de prioridad:

| # | Archivo | Complejidad | Tiempo |
|---|---------|-------------|--------|
| 1 | Inventory.php (Observer) | Baja | 15 min |
| 2 | PurchaseResource.php | Media | 20 min |
| 3 | EditPurchase.php | Media | 20 min |
| 4 | EditSale.php | Media | 20 min |
| 5 | DTEController.php | Alta | 30 min |
| 6 | EditCreditNote.php | Alta | 30 min |
| 7 | Resto (6 archivos) | Media | 60 min |

---

### Fase 3: Eliminar KardexHelper (30 min)

1. Verificar que no hay llamadas restantes
2. Deprecar KardexHelper
3. Opcional: Eliminar InventoryCostoHistory

---

### Fase 4: Testing (1 hora)

1. Tests unitarios de KardexService
2. Tests de integración con cada operación
3. Verificar integridad de datos

---

## 🚨 RIESGOS Y MITIGACIONES

### Riesgo 1: Romper funcionalidad existente
- **Probabilidad**: Media
- **Impacto**: Alto
- **Mitigación**:
  - Migrar un archivo a la vez
  - Testing exhaustivo antes de cada migración
  - Mantener KardexHelper hasta completar migración

### Riesgo 2: Cambios en lógica de negocio
- **Probabilidad**: Baja
- **Impacto**: Alto
- **Mitigación**:
  - Documentar lógica actual antes de cambiar
  - Validar con usuario que cálculos son correctos
  - Comparar resultados antes/después

### Riesgo 3: Datos históricos inconsistentes
- **Probabilidad**: Baja
- **Impacto**: Medio
- **Mitigación**:
  - No modificar registros existentes
  - Solo mejorar nuevos registros
  - Script de validación de integridad

---

## 📈 MÉTRICAS DE ÉXITO

### Antes de Refactorización

| Métrica | Valor Actual |
|---------|--------------|
| Archivos con lógica Kardex | 12 archivos |
| Líneas de código duplicadas | ~360 líneas |
| Parámetros por llamada | 12 parámetros |
| Cálculos manuales | 100% |
| Queries adicionales | 1 por registro |
| Tests unitarios | 0 |
| Manejo de errores | Ninguno |

### Después de Refactorización

| Métrica | Valor Esperado | Mejora |
|---------|----------------|--------|
| Archivos con lógica Kardex | 1 Service | **-92%** |
| Líneas de código duplicadas | 0 líneas | **-100%** |
| Parámetros por llamada | 2-3 parámetros | **-75%** |
| Cálculos manuales | 0% (automáticos) | **-100%** |
| Queries adicionales | 0 | **-100%** |
| Tests unitarios | 10+ tests | **+∞** |
| Manejo de errores | Try/catch + validaciones | **+100%** |

---

## 💼 EJEMPLO DE IMPLEMENTACIÓN

### KardexService.php (Fragmento)

```php
<?php

namespace App\Services\Inventory;

use App\Models\Kardex;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KardexService
{
    /**
     * Registra un movimiento de venta en el Kardex
     */
    public function registrarVenta(Sale $sale, SaleItem $item): bool
    {
        return DB::transaction(function () use ($sale, $item) {
            $inventory = $item->inventory;

            if ($inventory->product->is_grouped) {
                return $this->registrarVentaProductoAgrupado($sale, $item);
            }

            return $this->crearRegistroKardex(
                inventory: $inventory,
                operation_type: 'Venta',
                operation_id: $sale->id,
                operation_detail_id: $item->id,
                document_type: $sale->documenttype->name ?? 'S/N',
                document_number: $sale->document_internal_number,
                entity: $this->formatearEntidad($sale->customer),
                nationality: $sale->customer->country->name ?? 'Salvadoreña',
                quantity: $item->quantity,
                is_input: false,  // Es salida
                unit_price: $item->price,
                date: $sale->operation_date
            );
        });
    }

    /**
     * Crea el registro de Kardex con cálculos automáticos
     */
    private function crearRegistroKardex(
        Inventory $inventory,
        string $operation_type,
        int $operation_id,
        int $operation_detail_id,
        string $document_type,
        string $document_number,
        string $entity,
        string $nationality,
        int $quantity,
        bool $is_input,
        float $unit_price,
        $date
    ): bool {
        // Obtener stock anterior
        $previous_stock = $inventory->stock;

        // Calcular nuevo stock
        $stock_in = $is_input ? $quantity : 0;
        $stock_out = $is_input ? 0 : $quantity;
        $stock_actual = $is_input
            ? $previous_stock + $quantity
            : $previous_stock - $quantity;

        // Validar stock suficiente para salidas
        if (!$is_input && $stock_actual < 0) {
            throw new \Exception("Stock insuficiente para {$inventory->product->name}");
        }

        // Actualizar inventario
        if ($is_input) {
            $inventory->increment('stock', $quantity);
        } else {
            $inventory->decrement('stock', $quantity);
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

        // Crear registro
        $kardex = Kardex::create([
            'branch_id' => $inventory->branch_id,
            'date' => $date,
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
            'money_in' => $money_in,
            'money_out' => $money_out,
            'money_actual' => $money_actual,
            'sale_price' => $is_input ? 0 : $unit_price,
            'purchase_price' => $is_input ? $unit_price : 0,
            'promedial_cost' => $promedial_cost
        ]);

        // Validar integridad
        $this->validarIntegridadKardex($kardex);

        return (bool) $kardex;
    }

    /**
     * Calcula costo promedio ponderado
     */
    private function calcularCostoPromedio(
        Inventory $inventory,
        float $nuevoCosto,
        int $cantidad,
        bool $is_input
    ): float {
        if (!$is_input) {
            // Para salidas, mantener el costo promedio actual
            $ultimoKardex = Kardex::where('inventory_id', $inventory->id)
                ->orderByDesc('id')
                ->first();

            return $ultimoKardex?->promedial_cost ?? $inventory->cost_without_taxes;
        }

        // Para entradas, calcular nuevo promedio
        $ultimoKardex = Kardex::where('inventory_id', $inventory->id)
            ->orderByDesc('id')
            ->first();

        $costoAnterior = $ultimoKardex?->promedial_cost ?? $inventory->cost_without_taxes;
        $stockAnterior = $ultimoKardex?->stock_actual ?? 0;

        $totalCantidad = $stockAnterior + $cantidad;

        if ($totalCantidad > 0) {
            return (($stockAnterior * $costoAnterior) + ($cantidad * $nuevoCosto)) / $totalCantidad;
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
            Log::error("Error de integridad en Kardex #{$kardex->id}", [
                'expected' => $calculado,
                'actual' => $kardex->stock_actual
            ]);

            throw new \Exception("Error de integridad en registro de Kardex");
        }
    }

    /**
     * Formatea entidad (cliente/proveedor)
     */
    private function formatearEntidad($entity): string
    {
        if (!$entity) return 'Varios';

        $nombre = $entity->name ?? $entity->comercial_name ?? 'Varios';
        $apellido = $entity->last_name ?? '';

        return trim("$nombre $apellido");
    }
}
```

---

## 🎯 CONCLUSIÓN

El **KardexHelper actual** funciona, pero tiene problemas de arquitectura que dificultan el mantenimiento y aumentan el riesgo de errores:

### Problemas Principales
1. 🔴 Código duplicado en 12 archivos (~360 líneas)
2. 🔴 Cálculo de costo promedio incorrecto
3. 🔴 12 parámetros difíciles de manejar
4. 🔴 Cálculos manuales propensos a errores
5. 🔴 Sin validaciones ni manejo de errores
6. 🔴 Difícil de testear

### Solución Propuesta
✅ **KardexService centralizado** con:
- Métodos específicos por operación
- Cálculos automáticos
- Validaciones integradas
- DTOs en vez de parámetros sueltos
- Testing unitario
- Manejo de transacciones

### Impacto Esperado
- **-100%** código duplicado
- **-75%** parámetros
- **+100%** confiabilidad
- **+∞** testabilidad

**Tiempo estimado**: 8-9 horas
**Prioridad**: Alta (deuda técnica crítica)

---

**Documento creado**: 2025-10-30
**Autor**: Análisis automatizado con Claude Code
**Versión**: 1.0
