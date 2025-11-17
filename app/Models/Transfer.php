<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    /**
     * Relaciones cargadas por defecto (eager loading)
     * Evita N+1 queries en listados de transferencias
     */
    protected $with = ['wherehouseFrom', 'wherehouseTo', 'userSend', 'userRecive'];

    protected $fillable = [
        'transfer_number',
        'wherehouse_from',
        'user_send',
        'wherehouse_to',
        'user_recive',
        'transfer_date',
        'received_date',
        'total',
        'status_send',
        'status_received',
    ];

    public function wherehouseFrom()
    {
        return $this->belongsTo(Branch::class, 'wherehouse_from', 'id');
    }
    public function userSend()
    {
        return $this->belongsTo(Employee::class, 'user_send', 'id');
    }
    public function wherehouseTo()
    {
        return $this->belongsTo(Branch::class, 'wherehouse_to', 'id');
    }
    public function userRecive()
    {
        return $this->belongsTo(Employee::class,'user_recive','id');
    }

    public function transferDetails()
    {
        return $this->hasMany(TransferItems::class,'transfer_id','id');
    }


}
