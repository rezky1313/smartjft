<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. soal_kategori
        Schema::create('soal_kategori', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('jabatan')->nullable();
            $table->enum('jenjang', [
                'pemula', 'terampil', 'mahir', 'penyelia',
                'ahli_pertama', 'ahli_muda', 'ahli_madya', 'ahli_utama', 'umum',
            ]);
            $table->enum('matra', ['darat', 'laut', 'udara', 'asdp', 'umum']);
            $table->string('bidang')->nullable();
            $table->text('deskripsi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // 2. bank_soal
        Schema::create('bank_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soal_kategori_id')->nullable()->constrained('soal_kategori')->nullOnDelete();
            $table->text('pertanyaan');
            $table->text('pembahasan')->nullable();
            $table->enum('tingkat_kesulitan', ['mudah', 'sedang', 'sulit']);
            $table->enum('taksonomi_bloom', [
                'C1_mengingat', 'C2_memahami', 'C3_menerapkan',
                'C4_menganalisis', 'C5_mengevaluasi', 'C6_mencipta',
            ]);
            $table->enum('jenis', ['umum', 'spesifik'])->default('umum');
            $table->enum('status', ['aktif', 'nonaktif', 'draft'])->default('draft');
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('tanggal_disetujui')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. bank_soal_pilihan
        Schema::create('bank_soal_pilihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_soal_id')->constrained('bank_soal')->cascadeOnDelete();
            $table->enum('kode_pilihan', ['A', 'B', 'C', 'D']);
            $table->text('teks_pilihan');
            $table->boolean('is_benar')->default(false);
            $table->timestamps();
        });

        // 4. bank_soal_import_log
        Schema::create('bank_soal_import_log', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->integer('total_baris');
            $table->integer('berhasil');
            $table->integer('gagal');
            $table->json('detail_gagal')->nullable();
            $table->foreignId('diimport_oleh')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_soal_import_log');
        Schema::dropIfExists('bank_soal_pilihan');
        Schema::dropIfExists('bank_soal');
        Schema::dropIfExists('soal_kategori');
    }
};
