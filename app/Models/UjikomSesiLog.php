<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UjikomSesiLog extends Model
{
    use HasFactory;

    protected $table = 'ujikom_sesi_log';

    protected $fillable = [
        'ujikom_sesi_id',
        'aksi',
        'detail',
    ];

    protected $casts = [
        'detail' => 'array',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function sesi()
    {
        return $this->belongsTo(UjikomSesi::class, 'ujikom_sesi_id');
    }
}
