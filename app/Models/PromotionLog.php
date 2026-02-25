<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionLog extends Model
{
    protected $fillable = ['promotion_id','from_status','to_status','actor_id','note'];

    public function promotion(){ return $this->belongsTo(Promotion::class); }
    public function actor(){ return $this->belongsTo(User::class,'actor_id'); }
}
