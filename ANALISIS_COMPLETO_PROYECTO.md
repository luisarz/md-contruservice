# Análisis Completo del Proyecto: MD Contruservice

**Fecha de Análisis:** 2025-11-17
**Sistema:** Negocios y Servicios Clementina - ERP
**Versión:** Laravel 12.29.0 + Filament 4.1.8

---

## 📋 Resumen Ejecutivo

**Negocios y Servicios Clementina** es un **ERP (Enterprise Resource Planning)** completo desarrollado para El Salvador, especializado en gestión comercial con integración de **DTE (Documentos Tributarios Electrónicos)** de la autoridad fiscal salvadoreña (Hacienda).

---

## 🛠️ Stack Tecnológico

### Backend
- **Laravel 12.29.0** (PHP 8.2+)
- **Filament 4.1.8** - Panel de administración moderno
- **Livewire 3.4 + Volt** - Componentes reactivos
- **MariaDB** - Base de datos

### Frontend
- **Tailwind CSS 4.1.16**
- **Vite 5.0**
- **Alpine.js** (incluido con Livewire)

### Librerías Principales
- **Filament Shield** - Sistema de permisos y roles
- **DomPDF + mPDF** - Generación de PDFs
- **Simple QR Code** - Códigos QR para DTEs
- **Spatie Activity Log** - Auditoría de actividades
- **Filament Excel** - Exportación a Excel

---

## 🏗️ Arquitectura del Proyecto

### Estructura Base
```
md-contruservice/
├── app/
│   ├── Models/ (49 modelos)
│   ├── Filament/Resources/ (30+ recursos)
│   ├── Http/Controllers/ (14 controllers)
│   ├── Services/ (3 servicios)
│   ├── Helpers/ (DteHelper)
│   └── Console/Commands/ (2 comandos)
├── database/migrations/ (55+ migraciones)
├── routes/web.php (87 líneas)
└── resources/views/
```

### Dominios de Negocio

#### 1. **Facturación y DTEs** 🧾
- Generación de documentos electrónicos según normativa salvadoreña
- Tipos soportados: Facturas, CCF, Notas de Remisión, Crédito, Débito
- Transmisión a Hacienda (Normal/Contingencia)
- Generación de JSON, PDF, QR
- **Controller principal:** `DTEController.php` (1,367 líneas - identificado como deuda técnica)

#### 2. **Inventario y Kardex** 📦
- Sistema de inventario multi-sucursal
- **Kardex** con costo promedio ponderado
- Productos simples y agrupados (kits)
- Gestión de categorías, marcas, unidades de medida
- Precios por nivel (múltiples precios por producto)
- Ajustes de inventario

#### 3. **Ventas** 💰
- Ventas con múltiples condiciones (Contado/Crédito)
- Órdenes de trabajo
- Cotizaciones
- Notas de crédito
- Integración con cajas registradoras
- Correlativas por caja y tipo de documento

#### 4. **Compras** 🛒
- Compras a proveedores
- Notas de crédito de compras
- Retenciones fiscales
- Actualización automática de inventario

#### 5. **Caja** 💵
- Apertura/cierre de caja
- Correlativas automáticas por tipo de documento
- Caja chica (petty cash)
- Reportes de cierre

#### 6. **Transferencias** 🔄
- Traslados entre sucursales
- Registro en kardex (salida de origen, entrada en destino)
- Control de autorizaciones

#### 7. **Reportes** 📊
- Libro IVA (Facturas y CCF)
- Reportes de compras
- Reportes de inventario
- Reportes por empleado (vendedor)
- Descarga masiva de JSON/PDF de DTEs
- Exportación a Excel

---

## 🗄️ Base de Datos - Modelos Clave

### Jerarquía Principal

**Configuración:**
- `Company` → Empresa principal
- `Branch` → Sucursales
- `User`, `Employee` → Usuarios y empleados

**Inventario:**
- `Product` → Productos (SKU, código de barras)
- `Inventory` → Stock por sucursal
- `Kardex` → Registro de movimientos
- `Price` → Precios por nivel

**Operaciones:**
- `Sale` + `SaleItem` → Ventas
- `Purchase` + `PurchaseItem` → Compras
- `Transfer` + `TransferItems` → Transferencias
- `CreditNote`, `CreditNotePurchase` → Notas de crédito

**DTEs:**
- `HistoryDte` → Historial de documentos electrónicos (JSON completo)
- `DteTransmisionWherehouse` → Configuración DTE por sucursal
- `Contingency` → Registros de contingencias

**Caja:**
- `CashBox`, `CashBoxOpen`, `CashBoxCorrelative` → Cajas y correlativas

**Catálogos:**
- `DocumentType`, `PaymentMethod`, `Tribute`, `EconomicActivity`, etc.

---

## 📊 Estadísticas del Proyecto

### Modelos y Relaciones (49 modelos)

#### Modelos de Configuración Base:
```
Company              - Empresa principal
  ├─ economicactivity (BelongsTo)
  ├─ departamento (BelongsTo)
  ├─ distrito (BelongsTo)
  └─ country (BelongsTo)

Branch               - Sucursales
  ├─ company (BelongsTo)
  ├─ departamento (BelongsTo)
  ├─ distrito (BelongsTo)
  ├─ stablishmenttype (BelongsTo)
  └─ economicactivity (BelongsTo)

User                 - Usuarios del sistema
  └─ employee (BelongsTo)

Employee            - Empleados
  ├─ departamento (BelongsTo)
  ├─ municipio (BelongsTo)
  ├─ distrito (BelongsTo)
  ├─ wherehouse/branch (BelongsTo)
  └─ job (BelongsTo)
```

#### Modelos de Inventario:
```
Product             - Productos
  ├─ category (BelongsTo)
  ├─ marca (BelongsTo)
  ├─ unitmeasurement (BelongsTo)
  └─ inventories (HasMany)

Inventory           - Inventario por sucursal
  ├─ product (BelongsTo)
  ├─ branch (BelongsTo)
  └─ prices (HasMany)

Kardex              - Registro de movimientos de inventario
  ├─ whereHouse/branch (BelongsTo)
  └─ inventory (BelongsTo)

InventoryGrouped    - Inventario agrupado
Price               - Precios por producto
Category            - Categorías de productos
Marca               - Marcas
UnitMeasurement     - Unidades de medida
```

#### Modelos de Ventas:
```
Sale                - Ventas/Órdenes
  ├─ wherehouse/branch (BelongsTo)
  ├─ documenttype (BelongsTo)
  ├─ seller/employee (BelongsTo)
  ├─ mechanic/employee (BelongsTo)
  ├─ customer (BelongsTo)
  ├─ salescondition/operationcondition (BelongsTo)
  ├─ paymentmethod (BelongsTo)
  ├─ casher/employee (BelongsTo)
  ├─ billingModel (BelongsTo)
  ├─ transmisionType (BelongsTo)
  ├─ saleDetails/saleItems (HasMany)
  └─ dteProcesado/historydte (HasOne)

SaleItem            - Detalles de venta
  ├─ sale (BelongsTo)
  ├─ inventory (BelongsTo)
  └─ whereHouse/branch (BelongsTo)

CreditNote          - Notas de crédito para ventas
CreditNoteItem      - Detalles de notas de crédito
```

#### Modelos de Compras:
```
Purchase            - Compras a proveedores
  ├─ provider (BelongsTo)
  ├─ employee (BelongsTo)
  ├─ wherehouse/branch (BelongsTo)
  └─ purchaseItems (HasMany)

PurchaseItem        - Detalles de compra
Provider            - Proveedores
  ├─ country/pais (BelongsTo)
  └─ economicactivity (BelongsTo)

CreditNotePurchase  - Notas de crédito para compras
CreditNotePurchaseItem - Detalles de notas de crédito de compras
RetentionTaxe       - Retenciones fiscales
```

---

## 🔧 Controllers Principales (14 Controllers)

```
app/Http/Controllers/

1. DTEController.php (1,367 líneas - CRÍTICO)
   - generarDTE() - Genera DTE según tipo de documento
   - facturaJson(), CCFJson(), etc. - Genera JSON de diferentes tipos
   - anularDTE() - Anula un DTE
   - printDTETicket(), printDTEPdf() - Impresión
   - getConfiguracion() - Obtiene config de empresa

2. PurchaseController.php
   - generarPdf() - Genera PDF de compra

3. OrdenController.php
   - generarPdf() - Genera PDF de orden
   - ordenGenerarTicket() - Genera ticket de orden
   - closeClashBoxPrint() - Imprime cierre de caja

4. QuoteController.php
   - printQuote() - Imprime cotización

5. TransferController.php
   - printTransfer() - Imprime transferencia

6. ReportsController.php
   - saleReportFact(), saleReportCCF() - Reportes fiscales
   - purchaseReport() - Reporte de compras
   - downloadJson(), downloadPdf() - Descarga masiva

7. EmployeesController.php
   - sales() - Ventas por empleado
   - salesWork() - Trabajo realizado

8. InventoryReport.php
   - inventoryReportExport() - Exporta inventario
   - inventoryMovimentReportExport() - Exporta movimientos

9. SenEmailDTEController.php
   - Envía DTE por email

10. ContingencyController.php
    - contingencyDTE() - Crea contingencia
    - contingencyCloseDTE() - Cierra contingencia

11-14. ajustarController, AdjustementInventory, ControllerMigration, Controller
```

---

## 🛠️ Servicios (3 Servicios Principales)

### 1. CacheService.php
```php
Funciones:
- getTaxRate() - Obtiene tasa de impuesto (cache 1 hora)
- getDefaultTribute() - Obtiene tributo por defecto
- getTribute(string $name) - Tributo específico
- getCompanyConfig() - Config de empresa (cache 1 hora)
- clearConfigCache() - Limpia caches
```

### 2. DteFileService.php
```php
Funciones:
- generateTempJsonFile(string $codigoGeneracion)
- generateTempPdfFile(string $codigoGeneracion)
- generateQrBase64($DTE)
- generateTempFilesForEmail($codigoGeneracion)
- cleanTempFile($filePath)
- cleanOldTempFiles($hoursOld = 24)
```

### 3. KardexService.php
```php
Funciones:
- registrarInventarioInicial(Inventory $inventory)
- registrarCompra(Purchase $purchase, PurchaseItem $item)
- registrarVenta(Sale $sale, SaleItem $item)
- registrarTransferencia()
- registrarNotaCredito()
- registrarAjuste()
- crearRegistroKardex()
- recalcularKardex()
```

---

## 📁 Recursos Filament (30+ Recursos)

### Organización por Dominio:

**Configuración:**
- Companies, Branches, BillingModels

**Ubicación:**
- Countries, Departamentos, Municipalities, Distritos

**Catálogos:**
- Categories, Marcas, Products

**Personas:**
- Customers, Employees, Providers, PersonTypes

**Documentos:**
- DocumentTypes, PaymentMethods, OperationConditions

**Fiscal:**
- EconomicActivities, StablishmentTypes, Tributes

**Operaciones:**
- CashboxOpens, Cashboxes, Orders, Sales, CreditNotes
- Purchases, CreditNotePurchases, Transfers
- Contingencies, Kardexes, Inventories, AdjustmentInventories

---

## 🗂️ Migraciones Importantes (55+ Migraciones)

### Estructura Base:
- Tablas de catálogos (países, tributos, tipos de documento)
- Tablas de configuración (empresas, sucursales)

### Operaciones:
- Sales/Purchases (2024-10)
- Kardex (2024-10)
- Transfers (2025-01)
- Adjustments (2025-06)

### Optimizaciones Recientes (2025-10):
- `2025_10_29_230235_add_performance_indexes_to_tables.php`
- `2025_10_29_230429_fix_inventory_groupeds_foreign_key.php`
- `2025_10_30_100010_add_kardex_generated_to_purchases_table.php`
- `2025_10_30_220517_remove_json_url_from_sales_table.php`

---

## 🔑 Características Destacadas

### 1. **Sistema DTE Completo**
- Integración con API de Hacienda El Salvador
- Soporte de 10+ tipos de documentos
- Modo normal y contingencia
- Generación de archivos temporales (optimizado)
- Envío por email automático

### 2. **Kardex Inteligente**
- Registro automático de movimientos
- Cálculo de costo promedio ponderado
- Validación de integridad
- Comando: `php artisan kardex:recalculate`

### 3. **Multi-Sucursal**
- Inventario por sucursal
- Transferencias entre sucursales
- Configuración DTE por sucursal
- Cajas por sucursal

### 4. **Sistema de Permisos**
- Filament Shield integrado
- Roles y permisos granulares
- Políticas de autorización

### 5. **Caché Inteligente**
- CacheService para configuraciones
- TTL de 1 hora
- Reduce queries repetitivas

---

## ⚡ Optimizaciones Recientes

### 1. Optimización DTE (30-Oct-2025)
- ✅ Eliminado almacenamiento permanente de JSON/PDF/QR
- ✅ Generación temporal on-demand
- ✅ Limpieza automática diaria
- **Ahorro:** ~230MB por cada 10K DTEs

### 2. Optimización Base de Datos (29-Oct-2025)
- ✅ Índices compuestos en 7 tablas
- ✅ Corrección de foreign keys
- ✅ Mejora de queries con eager loading

---

## 🚨 Deuda Técnica Identificada

### Problemas Críticos (27 problemas totales)

#### 🔴 CRÍTICO
1. **DTEController** - 1,367 líneas (God Class)
2. **N+1 Query** en anulación de DTEs (100-200 queries extra)
3. **N+1 Query** en reportes de empleados (200+ queries)

#### 🟠 ALTO
4. `whereHas` ineficiente en búsquedas
5. Filtros insuficientes en Inventario
6. Lógica de Kardex duplicada (360+ líneas en 12 archivos)
7. Lógica de negocio en Controllers
8. Missing Observer para Sales

#### 🟡 MEDIO
9. SELECT * innecesario
10. Company::find(1) repetido
11. Mejoras UX/UI necesarias
12. SQL injection potential
13. Defer loading comentado

### Plan de Mejora Propuesto

**Sprint 1 (8 horas):**
- Fix N+1 Queries críticos
- Optimizar búsquedas
- Seguridad SQL
- Mejoras UX básicas

**Sprint 2 (10 horas):**
- Refactorizar DTEController → Services
- Crear SaleCalculationService
- Crear KardexService unificado
- Implementar Observers

**Mejora Esperada:** +150% rendimiento general

---

## 🎯 Comandos Útiles

### Desarrollo
```bash
php artisan serve                    # Servidor de desarrollo
npm run dev                          # Compilar assets (dev)
npm run build                        # Compilar assets (prod)
php artisan optimize:clear           # Limpiar todas las caches
```

### Kardex
```bash
php artisan kardex:recalculate       # Recalcular kardex
php artisan kardex:recalculate --inventory_id=123
php artisan kardex:recalculate --date=2025-01-01
php artisan kardex:recalculate --all
```

### DTEs
```bash
php artisan dte:clean-temp           # Limpiar archivos temporales >24h
php artisan dte:clean-temp --hours=12
```

### Filament
```bash
php artisan filament:optimize        # Optimizar Filament
php artisan filament:cache-components
php artisan shield:generate --all    # Generar permisos
php artisan shield:super-admin       # Crear super admin
```

### Base de Datos
```bash
php artisan migrate                  # Ejecutar migraciones
php artisan migrate:fresh --seed     # Reset completo
php artisan migrate:status           # Ver estado
```

### Caché
```bash
php artisan config:cache             # Cache de configuración
php artisan route:cache              # Cache de rutas
php artisan view:cache               # Cache de vistas
```

---

## 📊 Métricas del Proyecto

| Métrica | Valor |
|---------|-------|
| **Modelos Eloquent** | 49 modelos |
| **Recursos Filament** | 30+ recursos |
| **Controllers** | 14 controllers |
| **Servicios** | 3 servicios |
| **Migraciones** | 55+ migraciones |
| **Grupos de Navegación** | 10 grupos |
| **Tipos de DTE** | 12 tipos |
| **Líneas de Rutas** | 87 líneas |
| **Comandos Artisan** | 2 comandos custom |

---

## 🔒 Seguridad

- ✅ Autenticación Laravel Breeze
- ✅ Filament Shield para permisos
- ✅ SQL injection protegido (mayoría)
- ✅ Validaciones Filament
- ✅ Auditoría con Spatie Activity Log
- ⚠️ Algunas áreas con DB::raw sin binding

---

## 🌍 Configuración Regional

- **País:** El Salvador
- **Idioma:** Español (ES)
- **Moneda:** USD
- **Formato Fecha:** YYYY-MM-DD (ISO 8601)
- **Año Fiscal:** Calendario (Ene-Dic)
- **Impuesto Principal:** IVA 13%

---

## 📂 Archivos Críticos - No Modificar Sin Permiso

❌ **Requieren permiso explícito:**
- `app/Http/Controllers/DTEController.php` (1,367 líneas)
- `app/Helpers/KardexHelper.php` (hasta refactorización)
- Rutas DTE (`/generarDTE/*`, `/sendAnularDTE/*`)

✅ **Siempre actualizar cuando modifiques inventario:**
- Crear Kardex vía `KardexHelper::createKardexFromInventory()`
- Actualizar `inventory.stock`
- Manejar productos agrupados (`product.is_grouped`)

❌ **NUNCA usar en commits:**
- Footer "🤖 Generated with [Claude Code]"
- "Co-Authored-By: Claude <noreply@anthropic.com>"

---

## 💡 Patrones y Convenciones

### Eager Loading
```php
// ✅ CORRECTO - Cargar relaciones anticipadamente
Sale::with(['customer', 'saleDetails.inventory.product'])
    ->whereBetween('operation_date', [$start, $end])
    ->get();

// ❌ INCORRECTO - Causa N+1
Sale::whereBetween('operation_date', [$start, $end])->get();
foreach ($sales as $sale) {
    $customer = $sale->customer; // N+1!
}
```

### Pessimistic Locking (Correlativas)
```php
// ✅ CORRECTO - Previene race conditions
$correlative = CashBoxCorrelative::where('cash_box_id', $cashBoxId)
    ->where('document_type_id', $docTypeId)
    ->lockForUpdate()
    ->first();

$newNumber = $correlative->current_number + 1;
$correlative->update(['current_number' => $newNumber]);
```

### Actualización de Totales
```php
// Patrón común para recalcular totales de venta
function updateTotalSale(mixed $idItem, array $data): void {
    $sale = Sale::find($idItem);
    $montoTotal = SaleItem::where('sale_id', $sale->id)->sum('total');
    // Aplicar impuestos, retenciones...
    $sale->update([...]);
}
```

---

## 🗺️ Mapeo de Contextos de Dominio

### FACTURACIÓN
```
├── Models: Sale, SaleItem, HistoryDte, CreditNote
├── Controllers: DTEController, OrdenController, ReportsController
├── Services: DteFileService
├── Helpers: DteHelper
└── Resources: Orders, Sales, CreditNotes
```

### INVENTARIO
```
├── Models: Product, Inventory, Kardex, InventoryGrouped, Category, Marca
├── Controllers: InventoryReport
├── Services: KardexService
├── Commands: RecalculateKardex
└── Resources: Inventories, Kardexes, Categories, Marcas
```

### COMPRAS
```
├── Models: Purchase, PurchaseItem, Provider, RetentionTaxe, CreditNotePurchase
├── Controllers: PurchaseController, ReportsController
└── Resources: Purchases, Providers, CreditNotePurchases
```

### CAJA
```
├── Models: CashBox, CashBoxOpen, CashBoxCorrelative, SmallCashBoxOperation
└── Resources: Cashboxes, CashboxOpens
```

### TRANSFERENCIAS
```
├── Models: Transfer, TransferItems
├── Controllers: TransferController
└── Resources: Transfers
```

### CONFIGURACIÓN
```
├── Models: Company, Branch, User, Employee, Country, Departamento
├── Resources: Companies, Branches, Employees, Customers
└── Services: CacheService
```

### SEGURIDAD
```
├── Package: Spatie Permissions
├── Shield: Filament Shield
└── Policies: ActivityPolicy
```

---

## 🚀 Deployment

### Script Automático
```bash
chmod +x deploy.sh
./deploy.sh
```

### Manual
```bash
# 1. Modo mantenimiento
php artisan down

# 2. Actualizar código
git pull origin main

# 3. Dependencias
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 4. Storage
rm public/storage
php artisan storage:link

# 5. BD y caches
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# 6. Online
php artisan up
```

---

## 📚 Documentación Adicional

- `CLAUDE.md` - Guía completa para Claude Code
- `PLAN_OPTIMIZACION.md` - 27 problemas + plan de mejora
- `OPTIMIZACION_DTE.md` - Detalles optimización DTE
- `ANALISIS_KARDEX.md` - Análisis profundo Kardex
- `DEPLOYMENT.md` - Guía de deployment
- `IMPLEMENTACION_LOADER_PDF.md` - Loader para generación PDF
- `README.md` - Laravel estándar

---

## ✅ Estado Actual y Próximos Pasos

### Estado Actual
- ✅ Sistema **funcional en producción**
- ✅ Optimizaciones DTE implementadas (ahorro ~230MB)
- ✅ Índices de BD optimizados
- ✅ Documentación completa disponible

### Oportunidades de Mejora
- ⏳ Rendimiento (queries N+1)
- ⏳ Arquitectura (refactorizar DTEController)
- ⏳ Testing (0% cobertura actualmente)
- ⏳ Código duplicado (Kardex en 12 archivos)

### Recomendaciones Inmediatas
1. Implementar Sprint 1 del plan de optimización (8 horas)
2. Agregar tests unitarios a servicios críticos
3. Refactorizar DTEController a servicios especializados
4. Centralizar lógica Kardex en KardexService
5. Implementar monitoring (Laravel Telescope)

---

**Documento generado:** 2025-11-17
**Herramienta:** Claude Code Analysis
**Versión:** 1.0
