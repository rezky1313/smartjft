<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('formula_rf_master', function (Blueprint $table) {
            // Nilai literal konstan saat sumber_volume='konstanta_hari_kerja' -- TIDAK
            // selalu 240. Ditemukan lewat validasi silang RF-1B: mayoritas baris
            // (14 dari 15) memang literal 240 (representasi umum "20 hari kerja x 12
            // bulan"), tapi 1 baris Mahir ("Memperbaiki mayor peralatan pengujian
            // kendaraan") punya literal 10 di Excel sumber -- kalau semua dipaksa 240,
            // hasil ΣWpv Mahir meleset dari total resmi Excel.
            $table->decimal('volume_konstanta', 10, 2)->nullable()->after('sumber_volume');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('formula_rf_master', function (Blueprint $table) {
            $table->dropColumn('volume_konstanta');
        });
    }
};
