<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // doctrine/dbal tidak terinstall -> raw SQL untuk ubah enum & nullable
        DB::statement("ALTER TABLE pengangkatan_permohonan MODIFY COLUMN status ENUM('draft', 'diajukan', 'menunggu_ttd', 'selesai', 'ditolak') NOT NULL DEFAULT 'draft'");

        // ranking tidak dipakai lagi (tanpa seleksi/ranking) -> harus nullable
        DB::statement('ALTER TABLE pengangkatan_kandidat MODIFY COLUMN ranking INT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pengangkatan_kandidat MODIFY COLUMN ranking INT NOT NULL');

        DB::statement("ALTER TABLE pengangkatan_permohonan MODIFY COLUMN status ENUM('draft', 'diajukan', 'diproses', 'disetujui', 'ditolak', 'selesai') NOT NULL DEFAULT 'draft'");
    }
};
