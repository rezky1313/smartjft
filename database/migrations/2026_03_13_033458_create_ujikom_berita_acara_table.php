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
    }
};
