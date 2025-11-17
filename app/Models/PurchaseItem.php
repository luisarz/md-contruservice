<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    /**
     * Relaciones cargadas por defecto (eager loading)
     * Evita N+1 queries al acceder inventory.product en listados
     */
    protected $with = ['inventory.product'];

    protected $fillable = [
        'purchase_id',
        'inventory_id',
        'quantity',
        'price',
        'discount',
        'total',
    ];
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
