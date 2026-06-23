<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujikom_pendaftaran_peserta', function (Blueprint $table) {
            $table->unsignedBigInteger('jabatan_tujuan_id')->nullable()->after('pegawai_id');
            $table->string('jenjang_tujuan')->nullable()->after('jabatan_tujuan_id');
            $table->string('jabatan_tujuan_nama')->nullable()->after('jenjang_tujuan');

            $table->foreign('jabatan_tujuan_id')
                  ->references('id')
                  ->on('formasi_jabatan')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('ujikom_pendaftaran_peserta', function (Blueprint $table) {
            $table->dropForeign(['jabatan_tujuan_id']);
            $table->dropColumn(['jabatan_tujuan_id', 'jenjang_tujuan', 'jabatan_tujuan_nama']);
        });
    }
};
