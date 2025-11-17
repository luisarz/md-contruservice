<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashBoxCorrelative extends Model
{
   protected $fillable = [
       'cash_box_id',
       'document_type_id',
       'serie',
       'start_number',
       'end_number',
       'current_number',
       'is_active',
   ];

   public function cashBox()
   {
       return $this->belongsTo(CashBox::class,'cash_box_id','id');
   }
   public function document_type()
   {
         return $this->belongsTo(DocumentType::class,'document_type_id', 'id');

   }
}
