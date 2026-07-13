<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujikom_jadwal', function (Blueprint $table) {
            // Aspek untuk Kompetensi Teknis
            $table->boolean('teknis_wawancara_aktif')->default(false)->after('status');
            $table->boolean('teknis_presentasi_aktif')->default(false)->after('teknis_wawancara_aktif');
            // Aspek untuk Kompetensi Mansoskul
            $table->boolean('mansoskul_wawancara_aktif')->default(false)->after('teknis_presentasi_aktif');
            $table->boolean('mansoskul_presentasi_aktif')->default(false)->after('mansoskul_wawancara_aktif');
            // Jenjang tujuan (penentu bobot Teknis vs Mansoskul)
            $table->string('jenjang_tujuan')->nullable()->after('mansoskul_presentasi_aktif');
        });
    }

    public function down(): void
    {
        Schema::table('ujikom_jadwal', function (Blueprint $table) {
            $table->dropColumn([
                'teknis_wawancara_aktif',
                'teknis_presentasi_aktif',
                'mansoskul_wawancara_aktif',
                'mansoskul_presentasi_aktif',
                'jenjang_tujuan',
            ]);
        });
    }
};
