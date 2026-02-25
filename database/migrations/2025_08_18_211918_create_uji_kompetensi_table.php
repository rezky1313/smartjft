<?php

// database/migrations/2025_08_18_000000_create_uji_kompetensi_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('uji_kompetensi', function (Blueprint $t) {
      $t->id();
      $t->unsignedBigInteger('sdm_id'); // relasi ke sumber_daya_manusia.id
      $t->string('kompetensi', 20);     // PT1..PT5, Perpanjangan
      $t->decimal('nilai', 5, 2)->nullable();
      $t->date('tanggal_uji')->nullable();
      $t->string('nomor_sertifikat', 120)->nullable();
      $t->string('keterangan', 255)->nullable();
      $t->timestamps();

      $t->foreign('sdm_id')->references('id')->on('sumber_daya_manusia')
        ->cascadeOnUpdate()->restrictOnDelete();
      $t->index(['sdm_id','kompetensi']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('uji_kompetensi');
  }
};

