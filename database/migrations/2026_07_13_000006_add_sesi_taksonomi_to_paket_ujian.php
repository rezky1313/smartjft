<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // mode_pemilihan: tambah nilai baru 'sesi_taksonomi' (paket 2 sesi CAT: Teknis + Mansoskul).
        // Additive saja (tidak mengubah/menghapus nilai lama) jadi tidak perlu tahap migrasi data.
        DB::statement("ALTER TABLE paket_ujian MODIFY COLUMN mode_pemilihan ENUM('acak_otomatis', 'manual', 'sesi_taksonomi') NOT NULL DEFAULT 'acak_otomatis'");

        Schema::table('paket_ujian', function (Blueprint $table) {
            // Sesi Teknis
            $table->integer('durasi_menit_teknis')->nullable()->after('durasi_menit');
            $table->integer('jumlah_soal_teknis')->nullable()->after('durasi_menit_teknis');
            $table->enum('taksonomi_maks_teknis', ['C1_mengingat', 'C2_memahami', 'C3_menerapkan', 'C4_menganalisis', 'C5_mengevaluasi', 'C6_mencipta'])
                  ->nullable()->after('jumlah_soal_teknis');
            $table->unsignedBigInteger('soal_kategori_id_teknis')->nullable()->after('taksonomi_maks_teknis');

            // Sesi Mansoskul
            $table->integer('durasi_menit_mansoskul')->nullable()->after('soal_kategori_id_teknis');
            $table->integer('jumlah_soal_mansoskul')->nullable()->after('durasi_menit_mansoskul');
            $table->enum('taksonomi_maks_mansoskul', ['C1_mengingat', 'C2_memahami', 'C3_menerapkan', 'C4_menganalisis', 'C5_mengevaluasi', 'C6_mencipta'])
                  ->nullable()->after('jumlah_soal_mansoskul');
            $table->enum('matra_mansoskul', ['darat', 'laut', 'udara', 'asdp', 'perkeretaapian'])->nullable()->after('taksonomi_maks_mansoskul');
        });
    }

    public function down(): void
    {
        Schema::table('paket_ujian', function (Blueprint $table) {
            $table->dropColumn([
                'durasi_menit_teknis',
                'jumlah_soal_teknis',
                'taksonomi_maks_teknis',
                'soal_kategori_id_teknis',
                'durasi_menit_mansoskul',
                'jumlah_soal_mansoskul',
                'taksonomi_maks_mansoskul',
                'matra_mansoskul',
            ]);
        });

        DB::table('paket_ujian')->where('mode_pemilihan', 'sesi_taksonomi')->update(['mode_pemilihan' => 'acak_otomatis']);
        DB::statement("ALTER TABLE paket_ujian MODIFY COLUMN mode_pemilihan ENUM('acak_otomatis', 'manual') NOT NULL DEFAULT 'acak_otomatis'");
    }
};
