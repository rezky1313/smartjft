<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // doctrine/dbal tidak terinstall -> renameColumn() tidak bisa dipakai, pakai raw SQL
        DB::statement('ALTER TABLE rumahsakits RENAME COLUMN nama_rumahsakit TO nama_unit_kerja');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE rumahsakits RENAME COLUMN nama_unit_kerja TO nama_rumahsakit');
    }
};
