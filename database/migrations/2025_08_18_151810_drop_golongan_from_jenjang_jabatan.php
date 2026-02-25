<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jenjang_jabatan', function (Blueprint $t) {
            if (Schema::hasColumn('jenjang_jabatan', 'golongan')) {
                $t->dropColumn('golongan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jenjang_jabatan', function (Blueprint $t) {
            // sesuaikan panjang/nullable bila sebelumnya berbeda
            $t->string('golongan', 255)->nullable();
        });
    }
};
