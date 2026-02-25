<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionFile extends Model
{
    protected $fillable = ['promotion_id','kind','path','original_name','size','mime','is_valid'];

    public function promotion(){ return $this->belongsTo(Promotion::class); }
}
