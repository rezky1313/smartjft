<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ujikom_pendaftaran', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pendaftaran')->unique();
            $table->foreignId('ujikom_jadwal_id')
                  ->constrained('ujikom_jadwal')
                  ->onDelete('restrict');
            // unit_kerja_id mengacu ke no_rs (bigint unsigned) di rumahsakits
            $table->unsignedBigInteger('unit_kerja_id');
            $table->foreign('unit_kerja_id')
                  ->references('no_rs')
                  ->on('rumahsakits')
                  ->onDelete('restrict');
            $table->enum('jenis_pendaftaran', ['mandiri', 'batch'])->default('mandiri');
            $table->enum('status', [
                'draft',
                'diajukan_admin_unit',
                'diverifikasi_admin_unit',
                'diajukan_pusbin',
                'diverifikasi_pusbin',
                'ditolak_admin_unit',
                'ditolak_pusbin',
                'selesai',
            ])->default('draft');
            $table->text('catatan_admin_unit')->nullable();
            $table->text('catatan_pusbin')->nullable();
            $table->foreignId('dibuat_oleh')
                  ->constrained('users')
                  ->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('ujikom_pendaftaran_peserta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujikom_pendaftaran_id')
                  ->constrained('ujikom_pendaftaran')
                  ->onDelete('cascade');
            $table->foreignId('pegawai_id')
                  ->constrained('sumber_daya_manusia')
                  ->onDelete('restrict');
            $table->integer('sisa_formasi')->nullable();
            $table->enum('status_formasi', ['tersedia', 'tidak_tersedia'])->default('tersedia');
            $table->timestamps();
        });

        Schema::create('ujikom_pendaftaran_berkas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujikom_pendaftaran_id')
                  ->constrained('ujikom_pendaftaran')
                  ->onDelete('cascade');
            $table->foreignId('pegawai_id')
                  ->constrained('sumber_daya_manusia')
                  ->onDelete('restrict');
            $table->foreignId('ujikom_persyaratan_id')
                  ->constrained('ujikom_persyaratan')
                  ->onDelete('restrict');
            $table->string('nama_berkas');
            $table->string('file_path');
            $table->enum('status_verifikasi', ['belum', 'diterima', 'ditolak'])->default('belum');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujikom_pendaftaran_berkas');
        Schema::dropIfExists('ujikom_pendaftaran_peserta');
        Schema::dropIfExists('ujikom_pendaftaran');
    }
};
