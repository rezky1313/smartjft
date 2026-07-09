<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Regency;
use App\Models\Province;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitKerja extends Model
{
    use SoftDeletes;

    protected $table = 'unit_kerja'; // WAJIB, default Eloquent akan cari 'unit_kerjas'

    public $incrementing = true;
    protected $keyType = 'int';

    // primaryKey otomatis 'id' (default Laravel), tidak perlu deklarasi eksplisit lagi
    protected $fillable = [
        'nama_unit_kerja',
        'alamat',
        'no_telp',
        'latitude',
        'longitude',
        'regency_id',
        'matra',
        'instansi',
    ];

    public function formasis()
    {
        return $this->hasMany(\App\Models\Formasijabatan::class, 'unit_kerja_id');
    }

    public function regency()
    {
        return $this->belongsTo(Regency::class);
    }

    // akses provinsi lewat regency
    public function province()
    {
        return $this->regency?->province();
    }

}
