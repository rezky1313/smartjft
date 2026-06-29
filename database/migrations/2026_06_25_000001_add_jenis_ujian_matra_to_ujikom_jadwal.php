<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujikom_jadwal', function (Blueprint $table) {
            $table->enum('jenis_ujian', ['kenaikan_jabatan', 'perpindahan_jabatan', 'penyesuaian_inpassing'])
                  ->nullable()
                  ->after('judul');
            $table->string('matra', 100)
                  ->nullable()
                  ->after('jenis_ujian');
        });
    }

    public function down(): void
    {
        Schema::table('ujikom_jadwal', function (Blueprint $table) {
            $table->dropColumn(['jenis_ujian', 'matra']);
        });
    }
};
