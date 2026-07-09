<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('rumahsakits', 'unit_kerja');

        // doctrine/dbal tidak terinstall -> renameColumn() tidak bisa dipakai.
        // Raw SQL sekaligus jaga PRIMARY KEY + AUTO_INCREMENT (tipe asli dari
        // SHOW CREATE TABLE: bigint unsigned NOT NULL AUTO_INCREMENT).
        DB::statement('ALTER TABLE unit_kerja CHANGE no_rs id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE unit_kerja CHANGE id no_rs BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        Schema::rename('unit_kerja', 'rumahsakits');
    }
};
