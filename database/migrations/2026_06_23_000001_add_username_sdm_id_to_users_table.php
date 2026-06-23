<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('id');
            $table->unsignedBigInteger('sdm_id')->nullable()->after('username');
            $table->foreign('sdm_id')
                  ->references('id')
                  ->on('sumber_daya_manusia')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['sdm_id']);
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'sdm_id']);
        });
    }
};
