<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujikom_sesi', function (Blueprint $table) {
            $table->unsignedBigInteger('sesi_teknis_id')->nullable()->after('id');
            // 'tunggal' = sesi lama (mode paket acak_otomatis/manual, satu sesi CAT saja).
            // 'teknis'/'mansoskul' = sesi baru mode paket sesi_taksonomi (2 sesi CAT terpisah).
            $table->enum('jenis_sesi', ['tunggal', 'teknis', 'mansoskul'])->default('tunggal')->after('paket_ujian_id');
        });

        // Unique lama (ujikom_jadwal_id, peserta_id) mencegah >1 sesi per peserta per jadwal — harus
        // diperlonggar jadi per jenis_sesi supaya peserta bisa punya sesi Teknis + Mansoskul sekaligus.
        // Index baru dibuat DULU (index lama masih dipakai sbg backing index FK ujikom_jadwal_id,
        // MySQL tidak izinkan drop index itu sebelum ada index pengganti yang menutupi kolom yang sama).
        Schema::table('ujikom_sesi', function (Blueprint $table) {
            $table->unique(['ujikom_jadwal_id', 'peserta_id', 'jenis_sesi'], 'uq_ujikom_sesi_jadwal_peserta_jenis');
            $table->foreign('sesi_teknis_id')->references('id')->on('ujikom_sesi')->nullOnDelete();
        });

        DB::statement('ALTER TABLE ujikom_sesi DROP INDEX ujikom_sesi_ujikom_jadwal_id_peserta_id_unique');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE ujikom_sesi ADD UNIQUE ujikom_sesi_ujikom_jadwal_id_peserta_id_unique (ujikom_jadwal_id, peserta_id)');

        Schema::table('ujikom_sesi', function (Blueprint $table) {
            $table->dropForeign(['sesi_teknis_id']);
            $table->dropUnique('uq_ujikom_sesi_jadwal_peserta_jenis');
        });

        Schema::table('ujikom_sesi', function (Blueprint $table) {
            $table->dropColumn(['sesi_teknis_id', 'jenis_sesi']);
        });
    }
};
