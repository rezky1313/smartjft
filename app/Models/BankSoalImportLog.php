<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankSoalImportLog extends Model
{
    use HasFactory;

    protected $table = 'bank_soal_import_log';

    protected $fillable = [
        'nama_file',
        'total_baris',
        'berhasil',
        'gagal',
        'detail_gagal',
        'diimport_oleh',
    ];

    protected $casts = [
        'detail_gagal' => 'array',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function pengimport()
    {
        return $this->belongsTo(User::class, 'diimport_oleh');
    }
}
