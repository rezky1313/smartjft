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
        // Tabel pengangkatan_permohonan
        Schema::create('pengangkatan_permohonan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_permohonan', 100)->unique();
            $table->enum('jalur', ['inpasing', 'promosi', 'perpindahan_jabatan'])->default('inpasing');
            $table->unsignedBigInteger('unit_kerja_id');
            $table->string('file_surat_permohonan', 255)->nullable();
            $table->date('tanggal_permohonan');
            $table->enum('status', ['draft', 'diajukan', 'diverifikasi', 'draft_surat', 'paraf_katim', 'paraf_kabid', 'tanda_tangan', 'penomoran', 'selesai'])->default('draft');
            $table->text('catatan_verifikator')->nullable();
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
            $table->index('jalur');
            $table->index('unit_kerja_id');
            $table->index('tanggal_permohonan');
            $table->index('created_by');
        });

        // Tabel pengangkatan_peserta
        Schema::create('pengangkatan_peserta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pengangkatan_permohonan_id');
            $table->unsignedBigInteger('pegawai_id');

            // Data asal
            $table->string('jabatan_asal', 255)->nullable();
            $table->string('jenjang_asal', 50)->nullable();
            $table->unsignedBigInteger('unit_kerja_asal')->nullable();

            // Data tujuan
            $table->unsignedBigInteger('jabatan_tujuan_id')->nullable();
            $table->string('jenjang_tujuan', 50)->nullable();
            $table->unsignedBigInteger('unit_kerja_tujuan_id')->nullable();

            // Validasi
            $table->unsignedBigInteger('ujikom_peserta_id')->nullable();
            $table->enum('status_validasi_formasi', ['tersedia', 'tidak_tersedia'])->default('tersedia');
            $table->enum('status_validasi_ujikom', ['memenuhi', 'tidak_memenuhi'])->default('memenuhi');
            $table->text('catatan')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('pengangkatan_permohonan_id')
                ->references('id')
                ->on('pengangkatan_permohonan')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('pegawai_id')
                ->references('id')
                ->on('sumber_daya_manusia')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('jabatan_tujuan_id')
                ->references('id')
                ->on('formasi_jabatan')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('unit_kerja_tujuan_id')
                ->references('no_rs')
                ->on('rumahsakits')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('ujikom_peserta_id')
                ->references('id')
                ->on('ujikom_peserta')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Indexes
            $table->index('pengangkatan_permohonan_id');
            $table->index('pegawai_id');
            $table->index('status_validasi_formasi');
            $table->index('status_validasi_ujikom');

            // Unique constraint: satu pegawai hanya sekali per permohonan
            $table->unique(['pengangkatan_permohonan_id', 'pegawai_id'], 'pengangkatan_peserta_unique');
        });

        // Tabel pengangkatan_surat
        Schema::create('pengangkatan_surat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pengangkatan_permohonan_id');
            $table->string('nomor_surat', 255)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->unsignedBigInteger('dibuat_oleh');
            $table->timestamp('tanggal_dibuat')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('pengangkatan_permohonan_id')
                ->references('id')
                ->on('pengangkatan_permohonan')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('dibuat_oleh')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Indexes
            $table->index('pengangkatan_permohonan_id');
            $table->index('dibuat_oleh');

            // Unique constraint: satu surat pertimbangan per permohonan
            $table->unique('pengangkatan_permohonan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengangkatan_surat');
        Schema::dropIfExists('pengangkatan_peserta');
        Schema::dropIfExists('pengangkatan_permohonan');
    }
};
