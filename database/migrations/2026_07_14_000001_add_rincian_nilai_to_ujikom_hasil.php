<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catatan: kolom status_kecurangan SUDAH ADA (migration 2026_07_13_000012, PROMPT 3C) —
        // tidak ditambahkan lagi di sini. Kolom di bawah ini murni rincian mentah per aspek
        // untuk keperluan audit/tampilan detail (nilai_teknis/nilai_mansoskul yang sudah ada
        // adalah hasil GABUNGAN aspek-aspek ini, dihitung UjikomSesi::hitungNilaiKompetensi()).
        Schema::table('ujikom_hasil', function (Blueprint $table) {
            $table->decimal('nilai_teknis_cat', 5, 2)->nullable()->after('nilai_teknis');
            $table->decimal('nilai_teknis_wawancara', 5, 2)->nullable()->after('nilai_teknis_cat'); // skala 1-5
            $table->decimal('nilai_teknis_presentasi', 5, 2)->nullable()->after('nilai_teknis_wawancara'); // skala 1-5
            $table->decimal('nilai_mansoskul_cat', 5, 2)->nullable()->after('nilai_mansoskul');
            $table->decimal('nilai_mansoskul_wawancara', 5, 2)->nullable()->after('nilai_mansoskul_cat'); // skala 1-5
            $table->decimal('nilai_mansoskul_presentasi', 5, 2)->nullable()->after('nilai_mansoskul_wawancara'); // skala 1-5
        });
    }

    public function down(): void
    {
        Schema::table('ujikom_hasil', function (Blueprint $table) {
            $table->dropColumn([
                'nilai_teknis_cat',
                'nilai_teknis_wawancara',
                'nilai_teknis_presentasi',
                'nilai_mansoskul_cat',
                'nilai_mansoskul_wawancara',
                'nilai_mansoskul_presentasi',
            ]);
        });
    }
};
