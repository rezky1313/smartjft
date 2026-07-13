<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujikom_sesi_soal', function (Blueprint $table) {
            // khusus soal Mansoskul: nilai skala 1-5 dari pilihan yang dipilih peserta
            $table->tinyInteger('nilai_diperoleh')->nullable()->after('is_benar');
        });
    }

    public function down(): void
    {
        Schema::table('ujikom_sesi_soal', function (Blueprint $table) {
            $table->dropColumn('nilai_diperoleh');
        });
    }
};
