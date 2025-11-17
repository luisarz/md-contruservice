<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'nrc', 'nit', 'phone', 'whatsapp', 'email', 'logo', 'economic_activity_id', 'country_id', 'departamento_id','distrito_id', 'address', 'web', 'api_key'];
    protected $casts = [
        'logo' => 'array',
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id', 'id');
    }
    public function economicactivity(): BelongsTo
    {
        return $this->belongsTo(EconomicActivity::class, 'economic_activity_id', 'id');
    }
    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Distrito::class, 'distrito_id', 'id');
    }
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }


}
