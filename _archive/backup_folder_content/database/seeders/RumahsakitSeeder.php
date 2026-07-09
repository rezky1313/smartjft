<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Faker\Factory as Faker;

class RumahSakitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_no_rs');

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSUD Dr. SAIFUL ANWAR MALANG',
            'alamat' => 'Jl. Jaksa Agung Suprapto No.2, Klojen, Kec. Klojen, Kota Malang, Jawa Timur 65112',
            'no_telp' => '(0341) 362101',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'General Check UP RSSA, Antria Online Rawat Jalan',
            'latitude' => '-7.9723572',
            'longitude' => '112.627289',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'Rumah Sakit Lavalette',
            'alamat' => 'Jl. W.R. Supratman No.10, Rampal Celaket, Kec. Klojen, Kota Malang, Jawa Timur 65111',
            'no_telp' => '(0341) 470805',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Radiologi,Laboratorium,Hemodialisa,Cancer Center,Endoskopi,Operasi Katarak dengan Pacho,Fisiotrapi,Konsultasi Gizi,Home Care',
            'latitude' => '-7.9657494',
            'longitude' => '112.6336989',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSUD Kota Malang',
            'alamat' => 'Jl. Rajasa No.27, Bumiayu, Kec. Kedungkandang, Kota Malang, Jawa Timur 65116',
            'no_telp' => '(0341) 754338',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain',
            'latitude' => '-7.9779081',
            'longitude' => '112.5831843',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RS. Tk.II dr. Soepraoen',
            'alamat' => 'Jl. S. Supriadi No.22, Sukun, Kec. Sukun, Kota Malang, Jawa Timur 65112',
            'no_telp' => '(325111) 325112 / 0811-3229-9222',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain',
            'latitude' => '-7.989864',
            'longitude' => '112.6204795',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RS. Panti Nirmala',
            'alamat' => ' Jl. Kebalen Wetan No.2-8, Kotalama, Kec. Kedungkandang, Kota Malang, Jawa Timur 65134',
            'no_telp' => '(0341) 362459 / 331100 / 350833 / 0341360118',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain',
            'latitude' => '-7.994312',
            'longitude' => '112.6340581',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSIA Muhammadiyah Malang',
            'alamat' => 'Jl. K. H. Wahid Hasim, Kauman, Kec. Klojen, Kota Malang, Jawa Timur 65119',
            'no_telp' => '0813-1793-6288',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Tenaga Medis Profesional, Ruang Anak,Rawat Inap, IGD, Instalasi Gawat Darurat, Penunjang, Rawat Jalan, dan lain-lain',
            'latitude' => '-7.9841278',
            'longitude' => '112.6290835',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RS Universitas Brawijaya',
            'alamat' => 'Jl. Soekarno - Hatta, Lowokwaru, Kec. Lowokwaru, Kota Malang, Jawa Timur 65141',
            'no_telp' => '(0341) 403000',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain',
            'latitude' => '-7.9412275',
            'longitude' => '112.6215363',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RS. Panti Waluya Sawahan',
            'alamat' => 'Jl. Nusakambangan No.56, Kasin, Kec. Klojen, Kota Malang, Jawa Timur 65117',
            'no_telp' => '(0341) 366033',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain',
            'latitude' => '-7.986567',
            'longitude' => '112.6249675',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSI. Aisyiyah',
            'alamat' => 'Jl. Sulawesi No.16, Kasin, Kec. Klojen, Kota Malang, Jawa Timur 65117',
            'no_telp' => '(0341) 326773',
            'jam_kerja' => ' 24 Jam',
            'fasilitas' => ' Rawat Inap, Rawat Jalan, Radiologi, Kamar Bersalin, IGD, Instalasi Gawat Darurat, dan lain-lain',
            'latitude' => '-7.9886757',
            'longitude' => '112.6228673',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSI. UNISMA MALANG',
            'alamat' => 'Jalan, Jalan Mayjen Haryono No.139, Dinoyo, Kec. Lowokwaru, Kota Malang, Jawa Timur 65144',
            'no_telp' => '(0341) 551356',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain',
            'latitude' => '-7.9402626',
            'longitude' => '112.6088354',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RS Permata Bunda Malang',
            'alamat' => 'Jl. Soekarno Hatta No.75, Mojolangu, Kec. Lowokwaru, Kota Malang, Jawa Timur 65142',
            'no_telp' => '(0341) 407462 / 0877-9311-1347',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, Kamar Bersalin, IGD 24 Jam, Telekonsultasi, Penunjang, dan lain-lain',
            'latitude' => '-7.938896',
            'longitude' => '112.6248248',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSIA Hermina Malang',
            'alamat' => 'Jl. Tangkuban Perahu No.31-33, Kauman, Kec. Klojen, Kota Malang, Jawa Timur 65119',
            'no_telp' => '(0341) 322525',
            'jam_kerja' => '24 Jam',
            'fasilitas' => 'Rawat Inap,Rawat Jalan,Farmasi,NICU & PICU,Laboratorium, Kamar Bersalin, IGD, Instalasi Gawat Darurat, dan lain-lain',
            'latitude' => '-7.9780058',
            'longitude' => '112.624481',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSIA Husada Bunda',
            'alamat' => ' Malang No. 2, Jl. Pahlawan Trip, Oro-oro Dowo, Klojen, Malang City, East Java 65112',
            'no_telp' => '(0341) 566972',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, Rawat Jalan, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain',
            'latitude' => '-7.9682854',
            'longitude' => '112.6228173',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RS Persada Hospital malang',
            'alamat' => 'Kompleks Araya Business Centre Kav. 2-4 Jalan Panji Suroso Blimbing Purwodadi Blimbing Kota Malang Jawa Timur 65126 ID, Jl. Raden Panji Suroso No.4, Purwodadi, Kec. Blimbing, Kota Malang, Jawa Timur 65126',
            'no_telp' => '(0341) 2996333',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, Penunjang Medis, IGD, Medical Check-Up, dan lain-lain',
            'latitude' => '-7.9349858',
            'longitude' => '112.6502085',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'Rumah Sakit Ibu dan Anak Mardi Waloeja Kauman',
            'alamat' => 'Jalan Kauman No. 23, Kauman, Kec. Klojen, Kota Malang, Jawa Timur 65119',
            'no_telp' => '(0341) 362304',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain',
            'latitude' => '-7.9829967',
            'longitude' => '112.6278261',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSIA Puri',
            'alamat' => 'Jl. Taman Slamet No.20, Gading Kasri, Kec. Klojen, Kota Malang, Jawa Timur 65115',
            'no_telp' => '(0341) 325329',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, Rawat Jalan, Ruang Perawatan, dan lain-lain',
            'latitude' => '-7.9744496',
            'longitude' => '112.6218371',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSIA Permata Hati',
            'alamat' => 'Jl. Danau Toba, Lesanpuro, Kec. Kedungkandang, Kota Malang, Jawa Timur 65138',
            'no_telp' => '(0341) 718068 / 0821-3912-4075',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, Rawat Jalan, Ruang Perawatan, dan lain-lain',
            'latitude' => '-7.9797897',
            'longitude' => '112.6606777',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSIA Puri Bunda',
            'alamat' => 'Jl. Simpang Sulfat Utara No.60 A, Pandanwangi, Kec. Blimbing, Kota Malang, Jawa Timur 65126',
            'no_telp' => '(0341) 480047 / (0341) 477511',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, IGD 24 Jam, Radiologi, Konsultasi, Ruang Isolasi, Ambulance',
            'latitude' => '-7.9587645',
            'longitude' => '112.6555326',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSIA Mardi Waloeja Rampal',
            'alamat' => 'Jl. W.R. Supratman No.1, Rampal Celaket, Kec. Klojen, Kota Malang, Jawa Timur 65111',
            'no_telp' => '(0341) 364756',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain',
            'latitude' => '-7.964898',
            'longitude' => '112.6352319',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSIA Mutiara Bunda',
            'alamat' => 'Jl. Ciujung No.19, Purwantoro, Kec. Blimbing, Kota Malang, Jawa Timur 65126',
            'no_telp' => '(0341) 400403',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Poli Anak, Poli Obgyn, Poli Umum, IGD, Laboratorium, Farmasi, Ambulance',
            'latitude' => '-7.9533696',
            'longitude' => '112.641043',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSIA Melati Husada',
            'alamat' => 'Jl. Kawi No.32, Gading Kasri, Kec. Klojen, Kota Malang, Jawa Timur 65115',
            'no_telp' => '(0341) 325249 / (0341) 326573',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain',
            'latitude' => '-7.9761054',
            'longitude' => '112.6213605',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSIA Galeri Candra',
            'alamat' => 'Jl. Andong No.3, Jatimulyo, Kec. Lowokwaru, Kota Malang, Jawa Timur 65141',
            'no_telp' => '(0341) 478571',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Tenaga Medis Profesional, Rawat Inap, IGD, Instalasi Gawat Darurat, Penunjang, Rawat Jalan, dan lain-lain',
            'latitude' => '-7.947945',
            'longitude' => '112.619574',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSIA Rumkitban 05.08.02 Malang',
            'alamat' => 'Jl. Panglima Sudirman No.E20, Kesatrian, Kec. Blimbing, Kota Malang, Jawa Timur 65111',
            'no_telp' => '(0341) 354396',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain',
            'latitude' => '-7.9740832',
            'longitude' => '112.6387908',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSIA Refa Husada',
            'alamat' => 'Tlogowaru, Kec. Kedungkandang, Kota Malang, Jawa Timur 65132',
            'no_telp' => '(0341) 754075',
            'jam_kerja' => '24 jam',
            'fasilitas' => 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain',
            'latitude' => '-8.0357588',
            'longitude' => '112.6426445',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        DB::table('rumahsakits')->insert([
            'nama_rumahsakit' => 'RSKK. Onkologi Sentani',
            'alamat' => 'Jl. Danau Paniai, Madyopuro, Kec. Kedungkandang, Kota Malang, Jawa Timur 65139',
            'no_telp' => '(0341) 3022902 / 0811-3136-991',
            'jam_kerja' => ' 24 Jam',
            'fasilitas' => ' Rawat Inap, IGD, Pelayanan 24 Jam, dan lain-lain',
            'latitude' => '-7.9741313',
            'longitude' => '112.6663124',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

    }
}
