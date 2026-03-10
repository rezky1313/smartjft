<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Formasijabatan extends Model
{
     use SoftDeletes;
    use HasFactory;

    protected $table = 'formasi_jabatan'; // pastikan nama tabel sesuai di database

    protected $fillable = [
        'nama_formasi',
        'jenjang_id',
        'unit_kerja_id',
        'kuota',
        'tahun_formasi',
    ];

    public function jabatan()
    {
        return $this->belongsTo(Jenjangjabatan::class,'jenjang_id');
    }


    public function unitkerja()
    {
        return $this->belongsTo(Rumahsakit::class, 'unit_kerja_id', 'no_rs');
    }

       public function jenjang()
    {
        return $this->belongsTo(Jenjangjabatan::class, 'jenjang_id', 'id');
    }

    // relasi lain (unitKerja, jenjang, dst)

    // SDM aktif yang menempati formasi ini
    public function sdmAktif()
    {
        return $this->hasMany(Sdmmodels::class, 'formasi_jabatan_id')
                    ->where('aktif', true);
    }

    // accessor terisi & sisa (opsional dipakai di blade)
    protected $appends = ['terisi','sisa'];

    public function getTerisiAttribute(): int
    {
        // kalau sudah pakai withCount('sdmAktif as terisi'), prioritaskan atribut yang di-load
        if (array_key_exists('terisi', $this->attributes)) {
            return (int) $this->attributes['terisi'];
        }
        return $this->sdmAktif()->count();
    }

    public function getSisaAttribute(): int
    {
        $kuota = (int) ($this->kuota ?? 0);
        $terisi = (int) ($this->getTerisiAttribute() ?? 0);

        // Sisa bisa MINUS (over kuota diizinkan)
        return $kuota - $terisi;
    }

    // Helper untuk mendapatkan class CSS berdasarkan sisa
    public function getSisaClassAttribute(): string
    {
        $sisa = $this->sisa;

        if ($sisa < 0) {
            return 'text-danger fw-bold'; // Merah bold untuk over kuota
        } elseif ($sisa === 0) {
            return 'text-warning fw-bold'; // Kuning bold untuk penuh
        } else {
            return ''; // Normal
        }
    }

}
