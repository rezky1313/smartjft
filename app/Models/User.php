<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
//buat reset password
use Illuminate\Auth\Passwords\CanResetPassword;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, CanResetPassword, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'sdm_id',
        'unit_kerja_id',
        'password',
        'status',
    ];

    public function sdm()
    {
        return $this->belongsTo(\App\Models\Sdmmodels::class, 'sdm_id');
    }

    public function unitKerja()
    {
        return $this->belongsTo(\App\Models\Rumahsakit::class, 'unit_kerja_id', 'no_rs');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // 'password' => 'hashed',
    ];


}
