<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('unit_kerja', function (Blueprint $table) {
            $table->enum('jenis_instansi', ['kemenhub', 'dishub'])->nullable()->after('instansi');
        });

        // Backfill dari kolom instansi yang sudah ada -- ditelusuri dulu ke data
        // sungguhan: SEMUA unit_kerja.instansi='Daerah' (136 baris) namanya diawali
        // "Dinas Perhubungan ..." tanpa kecuali, jadi pemetaan ini 100% deterministik,
        // bukan tebakan. Sisanya (instansi='Pusat') otomatis jadi 'kemenhub'.
        DB::table('unit_kerja')->where('instansi', 'Daerah')->update(['jenis_instansi' => 'dishub']);
        DB::table('unit_kerja')->where('instansi', 'Pusat')->update(['jenis_instansi' => 'kemenhub']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_kerja', function (Blueprint $table) {
            $table->dropColumn('jenis_instansi');
        });
    }
};
