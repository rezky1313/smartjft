<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Regency;
use App\Models\Province;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rumahsakit extends Model
{
    use SoftDeletes;
  
public $incrementing = true;
protected $keyType = 'int';

    protected $primaryKey = 'no_rs';
    protected $fillable = [
        'nama_rumahsakit',
        'alamat',
        'no_telp',
        'latitude',
        'longitude',
        'regency_id',
          'matra',        // <—
    'instansi', 
    ];

    public function formasis()
{
    // unit_kerja_id di formasi mengacu ke no_rs di rumahsakits
    return $this->hasMany(\App\Models\Formasijabatan::class, 'unit_kerja_id', 'no_rs');
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
