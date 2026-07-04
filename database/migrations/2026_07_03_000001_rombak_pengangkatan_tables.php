<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop tabel lama (urutan FK)
        Schema::dropIfExists('pengangkatan_surat');
        Schema::dropIfExists('pengangkatan_peserta');
        Schema::dropIfExists('pengangkatan_permohonan');

        // 1. pengangkatan_permohonan (baru)
        Schema::create('pengangkatan_permohonan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_permohonan')->unique();
            $table->string('unit_kerja_id');
            $table->string('file_surat_permohonan')->nullable();
            $table->date('tanggal_permohonan');
            $table->enum('status', ['draft', 'diajukan', 'diproses', 'disetujui', 'ditolak', 'selesai'])->default('draft');
            $table->text('catatan_pusbin')->nullable();
            $table->foreignId('diajukan_oleh')->constrained('users');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users');
            $table->date('tanggal_disetujui')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. pengangkatan_kandidat (baru)
        Schema::create('pengangkatan_kandidat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengangkatan_permohonan_id')->constrained('pengangkatan_permohonan')->onDelete('cascade');
            $table->unsignedBigInteger('pegawai_id');
            $table->unsignedBigInteger('ujikom_hasil_id');
            $table->string('jabatan_asal');
            $table->string('jenjang_asal');
            $table->unsignedBigInteger('jabatan_tujuan_id');
            $table->string('jenjang_tujuan');
            $table->decimal('nilai_ujikom', 5, 2);
            $table->integer('ranking');
            $table->integer('formasi_tersedia');
            $table->enum('status_kandidat', ['direkomendasikan', 'antrian', 'ditolak_pusbin'])->default('antrian');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('pegawai_id')->references('id')->on('sumber_daya_manusia');
            $table->foreign('ujikom_hasil_id')->references('id')->on('ujikom_hasil');
            $table->foreign('jabatan_tujuan_id')->references('id')->on('formasi_jabatan');
        });

        // 3. pengangkatan_surat (baru)
        Schema::create('pengangkatan_surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengangkatan_permohonan_id')->constrained('pengangkatan_permohonan')->onDelete('cascade');
            $table->string('nomor_surat')->nullable();
            $table->string('file_surat')->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->boolean('ditandatangani')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengangkatan_surat');
        Schema::dropIfExists('pengangkatan_kandidat');
        Schema::dropIfExists('pengangkatan_permohonan');
    }
};
