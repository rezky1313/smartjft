<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // doctrine/dbal tidak terinstall -> enum->change() tidak bisa dipakai, pakai raw SQL
        DB::statement("ALTER TABLE soal_kategori MODIFY COLUMN matra ENUM('darat', 'laut', 'udara', 'asdp', 'perkeretaapian', 'umum') NOT NULL DEFAULT 'umum'");

        Schema::table('soal_kategori', function (Blueprint $table) {
            $table->enum('klasifikasi', ['keahlian', 'keterampilan', 'umum'])->default('umum')->after('matra');
        });
    }

    public function down(): void
    {
        Schema::table('soal_kategori', function (Blueprint $table) {
            $table->dropColumn('klasifikasi');
        });

        DB::statement("ALTER TABLE soal_kategori MODIFY COLUMN matra ENUM('darat', 'laut', 'udara', 'asdp', 'umum') NOT NULL DEFAULT 'umum'");
    }
};
