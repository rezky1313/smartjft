<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ujikom_jadwal', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('tempat');
            $table->integer('kuota');
            $table->enum('status', ['draft', 'published', 'selesai'])->default('draft');
            $table->foreignId('dibuat_oleh')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('ujikom_persyaratan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujikom_jadwal_id')
                  ->constrained('ujikom_jadwal')
                  ->onDelete('cascade');
            $table->string('nama_syarat');
            $table->text('keterangan')->nullable();
            $table->string('file_contoh')->nullable();
            $table->integer('urutan')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujikom_persyaratan');
        Schema::dropIfExists('ujikom_jadwal');
    }
};
