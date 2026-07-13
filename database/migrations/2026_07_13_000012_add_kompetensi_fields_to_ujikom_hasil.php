<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujikom_hasil', function (Blueprint $table) {
            // Nilai per kompetensi (mode paket sesi_taksonomi — 2 sesi CAT), sudah gabungan CAT+Wawancara+Presentasi
            $table->decimal('nilai_teknis', 5, 2)->nullable()->after('nilai');
            $table->decimal('nilai_mansoskul', 5, 2)->nullable()->after('nilai_teknis');
            $table->unsignedTinyInteger('bobot_teknis')->nullable()->after('nilai_mansoskul');
            $table->unsignedTinyInteger('bobot_mansoskul')->nullable()->after('bobot_teknis');
            $table->enum('status_kecurangan', ['normal', 'terindikasi'])->default('normal')->after('status_kelulusan');
        });
    }

    public function down(): void
    {
        Schema::table('ujikom_hasil', function (Blueprint $table) {
            $table->dropColumn(['nilai_teknis', 'nilai_mansoskul', 'bobot_teknis', 'bobot_mansoskul', 'status_kecurangan']);
        });
    }
};
