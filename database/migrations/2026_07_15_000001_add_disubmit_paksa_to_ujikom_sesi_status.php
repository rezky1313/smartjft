<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pisahkan status "waktu benar-benar habis" ('timeout') dari "disubmit paksa akibat
     * 3x pelanggaran anti-cheat" ('disubmit_paksa') — sebelumnya keduanya memakai nilai
     * 'timeout' yang sama sehingga tidak bisa dibedakan di riwayat/monitoring.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE ujikom_sesi MODIFY COLUMN status_sesi ENUM('menunggu', 'berlangsung', 'selesai', 'timeout', 'disubmit_paksa') NOT NULL DEFAULT 'menunggu'");
    }

    public function down(): void
    {
        DB::statement("UPDATE ujikom_sesi SET status_sesi = 'timeout' WHERE status_sesi = 'disubmit_paksa'");
        DB::statement("ALTER TABLE ujikom_sesi MODIFY COLUMN status_sesi ENUM('menunggu', 'berlangsung', 'selesai', 'timeout') NOT NULL DEFAULT 'menunggu'");
    }
};
