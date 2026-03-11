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
        // Tabel ujikom_permohonan
        Schema::create('ujikom_permohonan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_permohonan', 50)->unique();
            $table->unsignedBigInteger('unit_kerja_id');
            $table->string('file_surat_permohonan', 255)->nullable();
            $table->date('tanggal_permohonan');
            $table->enum('status', ['draft', 'diajukan', 'diverifikasi', 'terjadwal', 'selesai_uji', 'hasil_diinput', 'selesai'])->default('draft');
            $table->text('catatan_verifikator')->nullable();
            $table->date('tanggal_jadwal')->nullable();
            $table->string('tempat_ujikom', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('unit_kerja_id')
                ->references('no_rs')
                ->on('rumahsakits')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Indexes
            $table->index('status');
            $table->index('unit_kerja_id');
            $table->index('tanggal_permohonan');
            $table->index('created_by');
        });

        // Tabel ujikom_peserta
        Schema::create('ujikom_peserta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ujikom_permohonan_id');
            $table->unsignedBigInteger('pegawai_id');
            $table->enum('hasil', ['belum', 'lulus', 'tidak_lulus'])->default('belum');
            $table->text('catatan_hasil')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('ujikom_permohonan_id')
                ->references('id')
                ->on('ujikom_permohonan')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('pegawai_id')
                ->references('id')
                ->on('sumber_daya_manusia')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Indexes
            $table->index('ujikom_permohonan_id');
            $table->index('pegawai_id');
            $table->index('hasil');

            // Unique constraint: satu pegawai hanya bisa sekali dalam satu permohonan
            $table->unique(['ujikom_permohonan_id', 'pegawai_id']);
        });

        // Tabel ujikom_berita_acara
        Schema::create('ujikom_berita_acara', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ujikom_permohonan_id');
            $table->enum('jenis', ['verifikasi', 'hasil']);
            $table->string('file_path', 255)->nullable();
            $table->unsignedBigInteger('dibuat_oleh');
            $table->timestamp('tanggal_dibuat')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('ujikom_permohonan_id')
                ->references('id')
                ->on('ujikom_permohonan')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('dibuat_oleh')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Indexes
            $table->index('ujikom_permohonan_id');
            $table->index('jenis');
            $table->index('dibuat_oleh');

            // Unique constraint: satu jenis BA per permohonan
            $table->unique(['ujikom_permohonan_id', 'jenis']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ujikom_berita_acara');
        Schema::dropIfExists('ujikom_peserta');
        Schema::dropIfExists('ujikom_permohonan');
    }
};
