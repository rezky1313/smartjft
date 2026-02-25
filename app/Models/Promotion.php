<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = [
        'sdm_id','jenjang_asal_id','jenjang_target_id','status',
        'sk_number','sk_file_path','tmt_sk',
        'submitted_at','verified_at','applied_at',
        'created_by','updated_by','notes'
    ];

    const ST_DRAFT     = 'DRAFT';
    const ST_SUBMITTED = 'SUBMITTED';
    const ST_NEED_FIX  = 'NEED_FIX';
    const ST_VERIFIED  = 'VERIFIED';
    const ST_APPLIED   = 'APPLIED';

    protected $casts = [
        'submitted_at' => 'datetime',
        'verified_at'  => 'datetime',
        'applied_at'   => 'datetime',
        'tmt_sk'       => 'date',
    ];

    public function sdm() { return $this->belongsTo(Sdmmodels::class); }
    public function jenjangAsal() { return $this->belongsTo(JenjangJabatan::class,'jenjang_asal_id'); }
    public function jenjangTarget() { return $this->belongsTo(JenjangJabatan::class,'jenjang_target_id'); }
    public function files(): HasMany { return $this->hasMany(PromotionFile::class); }
    public function logs(): HasMany { return $this->hasMany(PromotionLog::class); }
}
