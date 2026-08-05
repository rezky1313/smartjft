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
        Schema::table('sumber_daya_manusia', function (Blueprint $table) {
            // Materialize Sdmmodels::getJenjangKodeAttribute() (Bagian 1, accessor PHP suffix-matching
            // dari formasi->jenjang->nama_jenjang) supaya bisa dipakai di WHERE/subquery SQL -- endpoint
            // server-side DataTables PKR-01 Bagian 3 butuh filter jenjang_kode tanpa load semua baris.
            // Backfill via `php artisan pkr:sync-jenjang-kode`, sinkron ke depan via Sdmmodels::syncJenjangKode().
            $table->string('jenjang_kode')->nullable()->index()->after('formasi_jabatan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sumber_daya_manusia', function (Blueprint $table) {
            $table->dropColumn('jenjang_kode');
        });
    }
};
