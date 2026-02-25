<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('sumber_daya_manusia', function (Blueprint $t) {
      $t->unsignedBigInteger('unit_kerja_id')->nullable()->after('formasi_jabatan_id');
      $t->foreign('unit_kerja_id')->references('no_rs')->on('rumahsakits')
        ->cascadeOnUpdate()->restrictOnDelete();
      $t->index('unit_kerja_id');
    });
  }
  public function down(): void {
    Schema::table('sumber_daya_manusia', function (Blueprint $t) {
      $t->dropForeign(['unit_kerja_id']);
      $t->dropColumn('unit_kerja_id');
    });
  }
};

