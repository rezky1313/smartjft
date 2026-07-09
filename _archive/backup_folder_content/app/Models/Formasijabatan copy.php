<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formasijabatan extends Model
{
    use HasFactory;

    public function unitkerja()
{
    return $this->belongsTo(Rumahsakit::class,'unit_kerja_id');
}

}

