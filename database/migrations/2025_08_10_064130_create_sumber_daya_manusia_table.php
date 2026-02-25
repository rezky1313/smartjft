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
        Schema::create('sumber_daya_manusia', function (Blueprint $t) {
            $t->bigIncrements('id');

            // Identitas
            $t->string('nip', 18)->nullable()->unique();
            $t->string('nik', 16)->nullable()->unique();
            $t->string('nama_lengkap', 150);
            $t->string('pangkat_golongan', 150);
            $t->enum('jenis_kelamin', ['L', 'P'])->index();

            // $t->string('tempat_lahir', 100)->nullable();
            // $t->date('tanggal_lahir')->nullable();

            // Pendidikan (opsional)
            $t->string('pendidikan_terakhir', 50)->nullable();
            // $t->string('program_studi', 120)->nullable();
            // $t->unsignedSmallInteger('tahun_lulus')->nullable();

            // Status kepegawaian (opsional)
            $t->enum('status_kepegawaian', ['PNS','PPPK','Non ASN'])->default('PNS');
            $t->boolean('aktif')->default(true)->index();

            // Hubungan ke formasi yang sedang ditempati (Opsi A)
            $t->unsignedBigInteger('formasi_jabatan_id')->nullable()->index();

            // TMT pengangkatan jenjang jft
            $t->date('tmt_pengangkatan')->nullable()->index();
            
            // Kontak & lain-lain
            // $t->string('email')->nullable();
            // $t->string('no_hp', 20)->nullable();
            // $t->text('alamat')->nullable();
            // $t->string('foto_path')->nullable();

            $t->timestamps();
            $t->softDeletes();

            // GANTI nama tabel di bawah sesuai yang ada di DB kamu:
            $t->foreign('formasi_jabatan_id')
              ->references('id')->on('formasi_jabatan') // kalau tabelmu 'formasi_jabatan', ubah baris ini
              ->nullOnDelete()
              ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sumber_daya_manusia');
    }
};
