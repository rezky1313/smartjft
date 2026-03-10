<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan kolom status
            $table->enum('status', ['active', 'inactive'])->default('active')->after('email_verified_at');

            // Hapus kolom role karena sudah diganti dengan Spatie
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kembalikan kolom role
            $table->enum('role', ['admin', 'user'])->default('user')->after('email_verified_at');

            // Hapus kolom status
            $table->dropColumn('status');
        });
    }
};
