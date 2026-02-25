<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('formasi_jabatan_histori', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('formasi_id')->nullable(); // id formasi sumber (boleh null kalau tidak ada)
            $t->unsignedInteger('unit_kerja_id');             // rumahsakits.no_rs
            $t->string('tahun_formasi', 10);
            $t->string('nama_formasi');
            $t->unsignedBigInteger('jenjang_id')->nullable();
            $t->integer('kuota')->default(0);
            $t->integer('terisi')->default(0);                // “foto” terisi saat snapshot
            $t->timestamp('snapshot_at')->useCurrent();       // kapan disimpan ke histori

            // index bantu
            $t->index(['unit_kerja_id','tahun_formasi']);
            $t->index('snapshot_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formasi_jabatan_histori');
    }
};

