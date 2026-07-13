<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: perluas enum dulu (superset lama+baru) supaya data lama tidak
        // hilang/error saat di-ALTER — doctrine/dbal tidak terinstall, pakai raw SQL.
        DB::statement("ALTER TABLE bank_soal MODIFY COLUMN jenis ENUM('umum', 'spesifik', 'mansoskul', 'teknis') NOT NULL DEFAULT 'teknis'");

        // Step 2: migrasi data lama ke istilah baru
        DB::table('bank_soal')->where('jenis', 'umum')->update(['jenis' => 'mansoskul']);
        DB::table('bank_soal')->where('jenis', 'spesifik')->update(['jenis' => 'teknis']);

        // Step 3: persempit enum ke nilai final
        DB::statement("ALTER TABLE bank_soal MODIFY COLUMN jenis ENUM('mansoskul', 'teknis') NOT NULL DEFAULT 'teknis'");

        // Step 4: kolom matra baru — khusus soal mansoskul (soal lama hasil migrasi dari
        // 'umum' otomatis matra = NULL, perlu dilengkapi manual via halaman edit)
        Schema::table('bank_soal', function (Blueprint $table) {
            $table->enum('matra', ['darat', 'laut', 'udara', 'asdp', 'perkeretaapian'])
                  ->nullable()->after('soal_kategori_id');
        });
    }

    public function down(): void
    {
        Schema::table('bank_soal', function (Blueprint $table) {
            $table->dropColumn('matra');
        });

        DB::statement("ALTER TABLE bank_soal MODIFY COLUMN jenis ENUM('umum', 'spesifik', 'mansoskul', 'teknis') NOT NULL DEFAULT 'spesifik'");

        DB::table('bank_soal')->where('jenis', 'mansoskul')->update(['jenis' => 'umum']);
        DB::table('bank_soal')->where('jenis', 'teknis')->update(['jenis' => 'spesifik']);

        DB::statement("ALTER TABLE bank_soal MODIFY COLUMN jenis ENUM('umum', 'spesifik') NOT NULL DEFAULT 'umum'");
    }
};
