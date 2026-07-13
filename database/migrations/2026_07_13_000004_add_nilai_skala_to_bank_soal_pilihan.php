<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_soal_pilihan', function (Blueprint $table) {
            // nilai 1-5, HANYA diisi untuk pilihan pada soal berjenis mansoskul.
            // is_benar tetap dipakai untuk soal teknis, nilai_skala untuk soal mansoskul.
            $table->tinyInteger('nilai_skala')->nullable()->after('is_benar');
        });
    }

    public function down(): void
    {
        Schema::table('bank_soal_pilihan', function (Blueprint $table) {
            $table->dropColumn('nilai_skala');
        });
    }
};
