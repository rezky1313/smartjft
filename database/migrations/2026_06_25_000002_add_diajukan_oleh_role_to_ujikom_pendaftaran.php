<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujikom_pendaftaran', function (Blueprint $table) {
            $table->string('diajukan_oleh_role', 50)->nullable()->after('dibuat_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('ujikom_pendaftaran', function (Blueprint $table) {
            $table->dropColumn('diajukan_oleh_role');
        });
    }
};
