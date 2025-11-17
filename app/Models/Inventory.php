<?php

namespace App\Models;

use Log;
use App\Services\Inventory\KardexService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Relaciones cargadas por defecto (eager loading)
     * Evita N+1 queries en listados de inventario
     */
    protected $with = ['product', 'branch'];

    protected $fillable = [
        'product_id',
        'branch_id',
        'stock',
        'stock_min',
        'stock_max',
        'cost_without_taxes',
        'cost_with_taxes',
        'is_stock_alert',
        'is_expiration_date',
        'is_active',
    ];

    protected static function booted()
    {
        parent::booted();
        static::created(function ($inventory) {
            try {
                app(KardexService::class)->registrarInventarioInicial($inventory);
            } catch (\Exception $e) {
                Log::error("Error al crear Kardex para inventario inicial: {$inventory->id}", [
                    'error' => $e->getMessage()
                ]);
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }
    public function inventoriesGrouped(): HasMany
    {
        return $this->hasMany(InventoryGrouped::class,'inventory_grouped_id','id');
    }

    /**
     * Relación con los registros de Kardex (historial de movimientos)
     */
    public function kardexes(): HasMany
    {
        return $this->hasMany(Kardex::class);
    }

}
