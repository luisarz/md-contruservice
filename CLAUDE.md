# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Negocios y Servicios Clementina** - Enterprise Resource Planning (ERP) system for El Salvador with DTE (Documentos Tributarios Electrónicos) integration.

- **Framework**: Laravel 12.29.0 (PHP 8.2+)
- **Admin Panel**: Filament 4.1.8
- **Database**: MariaDB
- **Frontend**: Livewire 3.4, Tailwind CSS 4.1.16, Vite 5.0
- **Country**: El Salvador (fiscal/tax regulations)

## Essential Commands

### Development
```bash
# Start development server
php artisan serve

# Build frontend assets (development)
npm run dev

# Build frontend assets (production)
npm run build

# Clear all caches
php artisan optimize:clear

# Clear specific caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Filament-specific
php artisan filament:optimize
php artisan filament:cache-components
```

### Database
```bash
# Run migrations
php artisan migrate

# Fresh database with seeders
php artisan migrate:fresh --seed

# Rollback last migration
php artisan migrate:rollback

# Check migration status
php artisan migrate:status
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Unit/ExampleTest.php

# Run with coverage
php artisan test --coverage
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Check code style without fixing
./vendor/bin/pint --test
```

## Architecture & Structure

### Core Business Logic

**DTE (Electronic Tax Documents) System**
- Central controller: `app/Http/Controllers/DTEController.php` (~1,367 lines - needs refactoring)
- Handles all electronic invoicing for El Salvador's tax authority (Hacienda)
- Manages transmission types: Normal (Previo) vs Contingency (Deferido)
- **CRITICAL**: Never modify DTEController without explicit user permission

**Inventory & Kardex System**
- `app/Helpers/KardexHelper.php` - Global helper autoloaded via composer.json
- Tracks all inventory movements (entries, exits, sales, purchases)
- Calculates weighted average costs (promedio ponderado)
- **Pattern**: Every inventory change MUST create a Kardex record

**Sales Flow**
1. Create Sale (draft) → Add SaleItems → Finalize Sale
2. On finalize: Updates inventory, creates Kardex, assigns correlative number, locks cash box
3. Uses pessimistic locking (`lockForUpdate()`) to prevent race conditions on correlatives
4. Supports: Regular sales, Credit notes, Invoices (CCF, Facturas)

**Purchase Flow**
1. Create Purchase → Add PurchaseItems → Finalize Purchase
2. Updates inventory costs, creates Kardex entries
3. Supports credit note purchases (returns)

### Filament Resources Organization

Resources are organized by domain in subdirectories:
```
app/Filament/Resources/
├── AdjustmentInventories/AdjustmentInventoryResource.php
├── Branches/BranchResource.php
├── CashboxOpens/CashboxOpenResource.php
├── Customers/CustomerResource.php
├── Inventories/InventoryResource.php
├── Products/ProductResource.php
├── Purchases/PurchaseResource.php
├── Sales/SaleResource.php
└── ...
```

Each Resource typically has:
- `Pages/` - Create, Edit, List, View pages
- `RelationManagers/` - Manage related data (e.g., SaleItemsRelationManager)

### Navigation Groups (AdminPanelProvider.php)

10 main groups (all collapsible):
1. **Almacén** - Warehouse management
2. **Inventario** - Inventory, products, prices
3. **Facturación** - Billing, sales, invoices
4. **Caja Chica** - Petty cash
5. **Contabilidad** - Accounting
6. **Libro Bancos** - Bank book
7. **Recursos Humanos** - HR
8. **Configuración** - Settings, company, branches
9. **Catálogos Hacienda** - Tax authority catalogs
10. **Seguridad** - Security, roles, permissions

**Custom behavior**: Only one navigation group can be open at a time (accordion-style via JavaScript in PanelsRenderHook::BODY_END)

### Key Models & Relationships

**Sale**
- `hasMany` SaleItem
- `belongsTo` Customer, DocumentType, CashBoxOpen, Employee (seller)
- `hasOne` HistoryDte (DTE processing record)

**Purchase**
- `hasMany` PurchaseItem
- `belongsTo` Provider, Branch

**Inventory**
- `belongsTo` Product, Branch
- `hasMany` Price (multiple price tiers)
- `hasMany` InventoryGrouped (for composite products)
- Tracks: stock, stock_min, stock_max, cost_with_taxes, cost_without_taxes

**Product**
- `belongsTo` Category, UnitMeasurement
- `hasMany` Inventory (one per branch)
- Fields: sku, bar_code, is_grouped (composite), is_inventory (tracks stock)

**Kardex (InventoryCostoHistory)**
- Tracks every inventory movement
- Fields: stock_in, stock_out, stock_actual, money_in, money_out, purchase_price, sale_price
- Linked to: operation_type, operation_id, document_type, branch

### Services Layer

**CacheService** (`app/Services/CacheService.php`)
- Caches frequently accessed data (Company config, Tribute rates)
- TTL: 3600 seconds (1 hour)
- Methods: `getDefaultTribute()`, `getCompanyConfig()`, `clearConfigCache()`

**GetCashBoxOpenedService** (`app/Service/GetCashBoxOpenedService.php`)
- Returns currently open cash box for authenticated user's branch
- Critical for sales/purchases (requires open cash box)

### Important Constraints & Business Rules

1. **Cash Box**: Must be open to finalize sales/purchases
2. **Correlatives**: Sequential document numbering per cash box + document type
3. **Race Conditions**: Use `lockForUpdate()` when reading/incrementing correlatives
4. **Contingency Mode**: Changes DTE transmission from Normal → Deferred
5. **Composite Products**: When selling grouped inventory, must update all child inventories
6. **Price Tiers**: Inventory can have multiple prices; one marked as `is_default`

### Common Patterns

**Eager Loading Convention**
```php
// Always eager load relationships used in loops
Sale::with(['customer', 'saleDetails.inventory.product'])
    ->whereBetween('operation_date', [$start, $end])
    ->get();
```

**Updating Related Totals Pattern**
```php
// Helper functions that recalculate Sale/Purchase totals after item changes
function updateTotalSale(mixed $idItem, array $data): void {
    $sale = Sale::find($idItem);
    $montoTotal = SaleItem::where('sale_id', $sale->id)->sum('total');
    // Apply taxes, retention...
    $sale->update([...]);
}
```

**Pessimistic Locking for Correlatives**
```php
$correlative = CashBoxCorrelative::where('cash_box_id', $cashBoxId)
    ->where('document_type_id', $docTypeId)
    ->lockForUpdate()
    ->first();

$newNumber = $correlative->current_number + 1;
// ... process sale ...
$correlative->update(['current_number' => $newNumber]);
```

## Database

### Key Tables
- `sales` - Main sales table
- `sale_items` - Line items for sales
- `purchases`, `purchase_items` - Purchase documents
- `inventories` - Stock per product per branch
- `inventory_costo_histories` (Kardex) - All inventory movements
- `cash_box_opens` - Cash box sessions
- `cash_box_correlatives` - Document numbering per session
- `history_dtes` - DTE processing status

### Recent Migrations
- `2025_10_29_230235_add_performance_indexes_to_tables.php` - Performance indexes on 7 tables
- `2025_10_29_230429_fix_inventory_groupeds_foreign_key.php` - Fixed foreign key bug

## Frontend & Styling

### Tailwind v4 Migration
Project uses **Tailwind CSS 4.1.16** (upgraded from v3):
- Config: `postcss.config.js` (no tailwind.config.js)
- Theme: `resources/css/filament/admin/theme.css` (uses `@theme {}` blocks)
- Custom colors defined in theme.css and AdminPanelProvider

### Vite Configuration
- Entry point: `resources/css/filament/admin/theme.css`
- Build output: `public/build/`
- Custom theme loaded via `->viteTheme()` in panel config

### UI Customization
- **Topbar hooks**: Display employee, branch, transmission mode
- **SPA mode**: Enabled with prefetching
- **Sidebar**: Collapsible, 17.5rem width, accordion groups
- **Font**: Poppins (loaded via panel config)

## Critical Files - Modification Rules

### DO NOT MODIFY without explicit permission:
- `app/Http/Controllers/DTEController.php` - DTE generation logic (1,367 lines)
- Routes related to DTE (`/generarDTE/*`, `/sendAnularDTE/*`, etc.)
- `app/Helpers/KardexHelper.php` - Inventory movement tracking

### ALWAYS update when modifying inventory:
- Create Kardex record via `KardexHelper::createKardexFromInventory()`
- Update `inventory.stock`
- Handle composite products (check `product.is_grouped`)

### NEVER use in git commits:
- "🤖 Generated with [Claude Code]" footer
- "Co-Authored-By: Claude <noreply@anthropic.com>"

## Security & Permissions

- **Shield Plugin**: Filament Shield for role-based permissions
- Auth guard: `web`
- Middleware: Standard Laravel + Filament middleware stack
- Roles visible in resources: `admin`, `super_admin`
- Custom login: `app/Filament/Auth/CustomLogin.php`

## Performance Optimizations Applied

1. **Database Indexes**: Composite indexes on frequently queried columns
2. **Eager Loading**: Using `->with()` for related data
3. **Caching**: CacheService for configuration data (1h TTL)
4. **Pessimistic Locking**: Prevents race conditions on correlatives
5. **Route Constraints**: Validation patterns for IDs, dates, UUIDs

## Known Issues & Technical Debt

1. **DTEController**: 1,367 lines - needs refactoring into services
2. **N+1 Queries**: Several Resources still missing eager loading
3. **Missing Tests**: Zero test coverage on business logic
4. **Duplicate Code**: Tax calculation repeated in multiple Resources
5. **Select * Queries**: Many queries load all columns unnecessarily

## Development Workflow

1. Create feature branch from `main`
2. Make changes, test locally
3. Run `./vendor/bin/pint` before committing
4. Commit with descriptive messages (no emoji footers)
5. Push and create PR if needed

## Environment Setup

1. Copy `.env.example` to `.env`
2. Configure database: MariaDB connection (default: `erp_dte`)
3. Generate key: `php artisan key:generate`
4. Run migrations: `php artisan migrate --seed`
5. Install dependencies: `composer install && npm install`
6. Build assets: `npm run build`
7. Create admin user via `php artisan shield:super-admin`

## Useful Artisan Commands

```bash
# Create Filament Resource
php artisan make:filament-resource ModelName --generate

# Create Shield permissions for Resource
php artisan shield:generate --all

# Create super admin user
php artisan shield:super-admin

# Upgrade Filament (auto-runs after composer update)
php artisan filament:upgrade
```

## Additional Notes

- **Locale**: El Salvador (Spanish) - forms, labels, notifications in Spanish
- **Currency**: USD (US Dollars)
- **Date Format**: YYYY-MM-DD (ISO 8601)
- **Fiscal Year**: Calendar year (Jan-Dec)
- **Tax Rate**: Cached via CacheService, typically 13% IVA

## Context for AI Assistants

When working on this codebase:
1. Always check if cash box is open before finalizing sales/purchases
2. Composite products (`is_grouped = true`) require updating child inventories
3. Use CacheService for frequently accessed config data
4. Apply pessimistic locking when incrementing correlatives
5. Create Kardex records for ALL inventory movements
6. Never modify DTEController without explicit user request
7. Follow PSR-4 naming conventions (recent cleanup applied)
8. Use route constraints for parameter validation
9. Prefer `exists()` over `count()` for boolean checks
10. Use `chunk()` for large dataset operations
