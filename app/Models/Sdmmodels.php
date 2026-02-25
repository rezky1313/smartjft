<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Sdmmodels extends Model
{
    use SoftDeletes;

    protected $table = 'sumber_daya_manusia';

    protected $fillable = [
        'nip',
        'nik',
        'nama_lengkap',
        'jenis_kelamin',
        'pendidikan_terakhir',
        'pangkat_golongan',
        'status_kepegawaian',
        'formasi_jabatan_id',
          'unit_kerja_id',   // <— penting
        'tmt_pengangkatan',
        'aktif',
    ];

    protected $casts = [
        'tmt_pengangkatan' => 'date',
        'aktif' => 'boolean',
    ];

    // Relasi ke Formasi (opsi A)
    public function formasi()
    {
        return $this->belongsTo(\App\Models\Formasijabatan::class, 'formasi_jabatan_id');
    }

    public function unitKerja() {
  return $this->belongsTo(\App\Models\Rumahsakit::class, 'unit_kerja_id', 'no_rs');
}

    // Atribut hitungan lama menjabat (pakai yg sudah kamu tulis pun boleh)
    public function getMasaJabatanAttribute(): ?string
    {
        if (!$this->tmt_pengangkatan) return null;
        $diff = $this->tmt_pengangkatan->diff(Carbon::today());
        return "{$diff->y} th {$diff->m} bln {$diff->d} hr";
    }
}
