<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        // Query para obtener los índices de la tabla
        $indexes = $connection->select(
            "SELECT INDEX_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ?
             AND TABLE_NAME = ?
             AND INDEX_NAME = ?",
            [$database, $table, $indexName]
        );

        return count($indexes) > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Índices para tabla sales
        Schema::table('sales', function (Blueprint $table) {
            if (!$this->indexExists('sales', 'idx_sales_date_status')) {
                // Búsquedas por fecha y estado
                $table->index(['operation_date', 'sale_status'], 'idx_sales_date_status');
            }

            if (!$this->indexExists('sales', 'idx_sales_dte_status')) {
                // Búsquedas por DTE
                $table->index(['is_dte', 'is_hacienda_send'], 'idx_sales_dte_status');
            }

            if (!$this->indexExists('sales', 'idx_sales_operation')) {
                // Búsquedas por tipo de operación
                $table->index(['operation_type', 'is_invoiced'], 'idx_sales_operation');
            }

            if (!$this->indexExists('sales', 'idx_sales_customer_date')) {
                // Búsquedas por cliente y fecha
                $table->index(['customer_id', 'operation_date'], 'idx_sales_customer_date');
            }

            if (!$this->indexExists('sales', 'idx_sales_warehouse_date')) {
                // Búsquedas por sucursal y fecha
                $table->index(['wherehouse_id', 'operation_date'], 'idx_sales_warehouse_date');
            }

            if (!$this->indexExists('sales', 'idx_sales_doctype_status')) {
                // Búsquedas por tipo de documento
                $table->index(['document_type_id', 'sale_status'], 'idx_sales_doctype_status');
            }
        });

        // Índices para tabla inventories
        Schema::table('inventories', function (Blueprint $table) {
            if (!$this->indexExists('inventories', 'unique_product_branch')) {
                // Prevenir duplicados de producto por sucursal
                $table->unique(['product_id', 'branch_id'], 'unique_product_branch');
            }

            if (!$this->indexExists('inventories', 'idx_inventory_active_stock')) {
                // Búsquedas por stock activo
                $table->index(['is_active', 'stock'], 'idx_inventory_active_stock');
            }

            if (!$this->indexExists('inventories', 'idx_inventory_branch_active')) {
                // Búsquedas por sucursal
                $table->index(['branch_id', 'is_active'], 'idx_inventory_branch_active');
            }
        });

        // Índices para tabla products
        Schema::table('products', function (Blueprint $table) {
            // Verificar si los índices ya existen antes de crearlos
            if (!$this->indexExists('products', 'idx_products_name_sku')) {
                // Búsquedas por nombre y SKU
                $table->index(['name', 'sku'], 'idx_products_name_sku');
            }

            if (!$this->indexExists('products', 'idx_products_barcode')) {
                // Búsqueda por código de barras
                $table->index('bar_code', 'idx_products_barcode');
            }

            if (!$this->indexExists('products', 'idx_products_active_service')) {
                // Búsqueda por estado activo y tipo de servicio
                $table->index(['is_active', 'is_service'], 'idx_products_active_service');
            }
        });

        // Índices para tabla sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            if (!$this->indexExists('sale_items', 'idx_saleitems_sale_inventory')) {
                // Búsquedas por venta
                $table->index(['sale_id', 'inventory_id'], 'idx_saleitems_sale_inventory');
            }
        });

        // Índices para tabla purchase_items
        Schema::table('purchase_items', function (Blueprint $table) {
            if (!$this->indexExists('purchase_items', 'idx_purchaseitems_purchase_inventory')) {
                // Búsquedas por compra
                $table->index(['purchase_id', 'inventory_id'], 'idx_purchaseitems_purchase_inventory');
            }
        });

        // Índices para tabla customers
        Schema::table('customers', function (Blueprint $table) {
            if (!$this->indexExists('customers', 'idx_customers_nit')) {
                // Búsquedas por documentos
                $table->index('nit', 'idx_customers_nit');
            }
            if (!$this->indexExists('customers', 'idx_customers_dui')) {
                $table->index('dui', 'idx_customers_dui');
            }
            if (!$this->indexExists('customers', 'idx_customers_nrc')) {
                $table->index('nrc', 'idx_customers_nrc');
            }

            if (!$this->indexExists('customers', 'idx_customers_fullname')) {
                // Búsquedas por nombre
                $table->index(['name', 'last_name'], 'idx_customers_fullname');
            }
        });

        // Índices para tabla inventory_costo_histories
        Schema::table('inventory_costo_histories', function (Blueprint $table) {
            if (!$this->indexExists('inventory_costo_histories', 'idx_kardex_inventory_date')) {
                // Búsquedas por inventario y fecha
                $table->index(['inventory_id', 'created_at'], 'idx_kardex_inventory_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Sales indexes
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_date_status');
            $table->dropIndex('idx_sales_dte_status');
            $table->dropIndex('idx_sales_operation');
            $table->dropIndex('idx_sales_customer_date');
            $table->dropIndex('idx_sales_warehouse_date');
            $table->dropIndex('idx_sales_doctype_status');
        });

        // Inventories indexes
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropUnique('unique_product_branch');
            $table->dropIndex('idx_inventory_active_stock');
            $table->dropIndex('idx_inventory_branch_active');
        });

        // Products indexes
        Schema::table('products', function (Blueprint $table) {
            if ($this->indexExists('products', 'idx_products_name_sku')) {
                $table->dropIndex('idx_products_name_sku');
            }
            if ($this->indexExists('products', 'idx_products_barcode')) {
                $table->dropIndex('idx_products_barcode');
            }
            if ($this->indexExists('products', 'idx_products_active_service')) {
                $table->dropIndex('idx_products_active_service');
            }
        });

        // Sale items indexes
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('idx_saleitems_sale_inventory');
        });

        // Purchase items indexes
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropIndex('idx_purchaseitems_purchase_inventory');
        });

        // Customers indexes
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_nit');
            $table->dropIndex('idx_customers_dui');
            $table->dropIndex('idx_customers_nrc');
            $table->dropIndex('idx_customers_fullname');
        });

        // Kardex indexes
        Schema::table('inventory_costo_histories', function (Blueprint $table) {
            if ($this->indexExists('inventory_costo_histories', 'idx_kardex_inventory_date')) {
                $table->dropIndex('idx_kardex_inventory_date');
            }
        });
    }
};
