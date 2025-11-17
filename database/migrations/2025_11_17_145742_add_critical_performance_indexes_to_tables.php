<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega índices críticos para optimizar consultas frecuentes y evitar N+1 queries.
     * Basado en análisis de performance del 2025-11-17.
     */
    public function up(): void
    {
        // ========================================
        // CRÍTICO: KARDEX - Tabla más consultada
        // ========================================
        Schema::table('kardex', function (Blueprint $table) {
            $table->index('operation_type', 'idx_kardex_operation_type');
            $table->index('date', 'idx_kardex_date');
            $table->index(['branch_id', 'date'], 'idx_kardex_branch_date');
            $table->index(['inventory_id', 'date', 'operation_type'], 'idx_kardex_inv_date_type');
            $table->index(['operation_type', 'date'], 'idx_kardex_operation_date');
        });

        // ========================================
        // CRÍTICO: PURCHASES - Foreign keys sin índices
        // ========================================
        Schema::table('purchases', function (Blueprint $table) {
            $table->index('provider_id', 'idx_purchases_provider');
            $table->index('employee_id', 'idx_purchases_employee');
            $table->index('wherehouse_id', 'idx_purchases_wherehouse');
            $table->index('purchase_date', 'idx_purchases_date');
            $table->index('status', 'idx_purchases_status');
            $table->index('kardex_generated', 'idx_purchases_kardex');
            // Índices compuestos para consultas frecuentes
            $table->index(['provider_id', 'purchase_date'], 'idx_purchases_provider_date');
            $table->index(['status', 'purchase_date'], 'idx_purchases_status_date');
        });

        // ========================================
        // CRÍTICO: SALES - Índices adicionales
        // ========================================
        Schema::table('sales', function (Blueprint $table) {
            $table->index('cashbox_open_id', 'idx_sales_cashbox');
            $table->index(['cashbox_open_id', 'sale_status'], 'idx_sales_cashbox_status');
            $table->index(['seller_id', 'operation_date'], 'idx_sales_seller_date');
        });

        // ========================================
        // CRÍTICO: HISTORY_DTES - Búsquedas de DTEs
        // ========================================
        Schema::table('history_dtes', function (Blueprint $table) {
            $table->index('codigoGeneracion', 'idx_history_codigo');
            $table->index('estado', 'idx_history_estado');
            $table->index(['selloRecibido', 'codigoGeneracion'], 'idx_history_sello_codigo');
            $table->index(['sales_invoice_id', 'selloRecibido'], 'idx_history_invoice_sello');
        });

        // ========================================
        // ALTO: TRANSFERS - Traslados entre bodegas
        // ========================================
        Schema::table('transfers', function (Blueprint $table) {
            $table->index('wherehouse_from', 'idx_transfers_from');
            $table->index('wherehouse_to', 'idx_transfers_to');
            $table->index('user_send', 'idx_transfers_user_send');
            $table->index('user_recive', 'idx_transfers_user_recive');
            $table->index('status_send', 'idx_transfers_status_send');
            $table->index('status_received', 'idx_transfers_status_received');
            $table->index('transfer_date', 'idx_transfers_date');
            // Índices compuestos
            $table->index(['wherehouse_from', 'status_send'], 'idx_transfers_from_status');
            $table->index(['wherehouse_to', 'status_received'], 'idx_transfers_to_status');
        });

        // ========================================
        // ALTO: TRANSFER_ITEMS
        // ========================================
        Schema::table('transfer_items', function (Blueprint $table) {
            $table->index(['transfer_id', 'inventory_id'], 'idx_transfer_items_composite');
            $table->index('inventory_id', 'idx_transfer_items_inventory');
        });

        // ========================================
        // ALTO: CASH_BOX_OPENS - Cajas abiertas
        // ========================================
        Schema::table('cash_box_opens', function (Blueprint $table) {
            $table->index('cashbox_id', 'idx_cashbox_opens_cashbox');
            $table->index('open_employee_id', 'idx_cashbox_opens_open_emp');
            $table->index('close_employee_id', 'idx_cashbox_opens_close_emp');
            $table->index('status', 'idx_cashbox_opens_status');
            $table->index(['cashbox_id', 'status', 'opened_at'], 'idx_cashbox_opens_composite');
        });

        // ========================================
        // ALTO: CUSTOMERS - Filtros frecuentes
        // ========================================
        Schema::table('customers', function (Blueprint $table) {
            $table->index('wherehouse_id', 'idx_customers_wherehouse');
            $table->index(['wherehouse_id', 'is_active'], 'idx_customers_wh_active');
            $table->index(['is_credit_client', 'is_active'], 'idx_customers_credit');
        });

        // ========================================
        // ALTO: PRODUCTS - Catálogos
        // ========================================
        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id', 'idx_products_category');
            $table->index('marca_id', 'idx_products_marca');
            $table->index('unit_measurement_id', 'idx_products_unit');
            $table->index(['category_id', 'is_active'], 'idx_products_cat_active');
        });

        // ========================================
        // ALTO: EMPLOYEES - Empleados por sucursal
        // ========================================
        Schema::table('employees', function (Blueprint $table) {
            $table->index('branch_id', 'idx_employees_branch');
            $table->index('job_title_id', 'idx_employees_job');
            $table->index('is_active', 'idx_employees_active');
            $table->index(['branch_id', 'is_active'], 'idx_employees_branch_active');
        });

        // ========================================
        // ALTO: SALE_ITEMS y PURCHASE_ITEMS
        // ========================================
        Schema::table('sale_items', function (Blueprint $table) {
            $table->index('inventory_id', 'idx_sale_items_inventory');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->index('inventory_id', 'idx_purchase_items_inventory');
        });

        // ========================================
        // MEDIO: BRANCHES, PROVIDERS
        // ========================================
        Schema::table('branches', function (Blueprint $table) {
            $table->index('company_id', 'idx_branches_company');
            $table->index('stablisment_type_id', 'idx_branches_stablishment');
            $table->index('is_active', 'idx_branches_active');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->index('country_id', 'idx_providers_country');
            $table->index('economic_activity_id', 'idx_providers_economic');
            $table->index('is_active', 'idx_providers_active');
        });

        // ========================================
        // MEDIO: INVENTORIES - Adicionales
        // ========================================
        Schema::table('inventories', function (Blueprint $table) {
            $table->index(['stock', 'stock_min'], 'idx_inventories_stock_levels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // KARDEX
        Schema::table('kardex', function (Blueprint $table) {
            $table->dropIndex('idx_kardex_operation_type');
            $table->dropIndex('idx_kardex_date');
            $table->dropIndex('idx_kardex_branch_date');
            $table->dropIndex('idx_kardex_inv_date_type');
            $table->dropIndex('idx_kardex_operation_date');
        });

        // PURCHASES
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('idx_purchases_provider');
            $table->dropIndex('idx_purchases_employee');
            $table->dropIndex('idx_purchases_wherehouse');
            $table->dropIndex('idx_purchases_date');
            $table->dropIndex('idx_purchases_status');
            $table->dropIndex('idx_purchases_kardex');
            $table->dropIndex('idx_purchases_provider_date');
            $table->dropIndex('idx_purchases_status_date');
        });

        // SALES
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_cashbox');
            $table->dropIndex('idx_sales_cashbox_status');
            $table->dropIndex('idx_sales_seller_date');
        });

        // HISTORY_DTES
        Schema::table('history_dtes', function (Blueprint $table) {
            $table->dropIndex('idx_history_codigo');
            $table->dropIndex('idx_history_estado');
            $table->dropIndex('idx_history_sello_codigo');
            $table->dropIndex('idx_history_invoice_sello');
        });

        // TRANSFERS
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropIndex('idx_transfers_from');
            $table->dropIndex('idx_transfers_to');
            $table->dropIndex('idx_transfers_user_send');
            $table->dropIndex('idx_transfers_user_recive');
            $table->dropIndex('idx_transfers_status_send');
            $table->dropIndex('idx_transfers_status_received');
            $table->dropIndex('idx_transfers_date');
            $table->dropIndex('idx_transfers_from_status');
            $table->dropIndex('idx_transfers_to_status');
        });

        // TRANSFER_ITEMS
        Schema::table('transfer_items', function (Blueprint $table) {
            $table->dropIndex('idx_transfer_items_composite');
            $table->dropIndex('idx_transfer_items_inventory');
        });

        // CASH_BOX_OPENS
        Schema::table('cash_box_opens', function (Blueprint $table) {
            $table->dropIndex('idx_cashbox_opens_cashbox');
            $table->dropIndex('idx_cashbox_opens_open_emp');
            $table->dropIndex('idx_cashbox_opens_close_emp');
            $table->dropIndex('idx_cashbox_opens_status');
            $table->dropIndex('idx_cashbox_opens_composite');
        });

        // CUSTOMERS
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_wherehouse');
            $table->dropIndex('idx_customers_wh_active');
            $table->dropIndex('idx_customers_credit');
        });

        // PRODUCTS
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_category');
            $table->dropIndex('idx_products_marca');
            $table->dropIndex('idx_products_unit');
            $table->dropIndex('idx_products_cat_active');
        });

        // EMPLOYEES
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_employees_branch');
            $table->dropIndex('idx_employees_job');
            $table->dropIndex('idx_employees_active');
            $table->dropIndex('idx_employees_branch_active');
        });

        // SALE_ITEMS y PURCHASE_ITEMS
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('idx_sale_items_inventory');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_items_inventory');
        });

        // BRANCHES
        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex('idx_branches_company');
            $table->dropIndex('idx_branches_stablishment');
            $table->dropIndex('idx_branches_active');
        });

        // PROVIDERS
        Schema::table('providers', function (Blueprint $table) {
            $table->dropIndex('idx_providers_country');
            $table->dropIndex('idx_providers_economic');
            $table->dropIndex('idx_providers_active');
        });

        // INVENTORIES
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropIndex('idx_inventories_stock_levels');
        });
    }
};
