<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashBox extends Model
{
    protected $fillable = [
        'branch_id',
        'description',
        'balance',
        'is_active',
        'is_open',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);

    }
    public function correlatives()
    {
        return $this->hasMany(CashBoxCorrelative::class);
    }
}
