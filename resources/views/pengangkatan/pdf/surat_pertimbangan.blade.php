<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Surat Pertimbangan Pengangkatan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
        }

        .container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 20mm 25mm 20mm 25mm;
        }

        .kop-surat {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
        }

        .kop-surat img {
            width: 80px;
            height: auto;
        }

        .kop-surat h2 {
            font-size: 14pt;
            font-weight: bold;
            margin: 5px 0;
        }

        .kop-surat h3 {
            font-size: 12pt;
            font-weight: normal;
            margin: 5px 0;
        }

        .kop-surat p {
            font-size: 10pt;
            margin: 3px 0;
        }

        .judul-surat {
            text-align: center;
            margin: 30px 0 20px 0;
        }

        .judul-surat h1 {
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }

        .nomor-surat {
            text-align: center;
            margin: 10px 0 20px 0;
        }

        .nomor-surat p {
            font-size: 12pt;
            font-weight: bold;
        }

        .isi-surat {
            margin: 20px 0;
        }

        .isi-surat p {
            margin-bottom: 10px;
            text-align: justify;
        }

        .isi-surat .salam {
            margin-bottom: 15px;
        }

        .tabel-peserta {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .tabel-peserta th,
        .tabel-peserta td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-size: 11pt;
        }

        .tabel-peserta th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .tabel-peserta td {
            vertical-align: top;
        }

        .tabel-peserta .no {
            text-align: center;
            width: 30px;
        }

        .catatan-khusus {
            margin: 20px 0;
            padding: 10px;
            border-left: 3px solid #d00;
            background-color: #fff5f5;
        }

        .catatan-khusus p {
            font-size: 11pt;
            margin: 5px 0;
        }

        .penutup {
            margin: 30px 0;
        }

        .ttd-container {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            page-break-inside: avoid;
        }

        .ttd-box {
            width: 45%;
            text-align: center;
        }

        .ttd-box p {
            margin: 5px 0;
        }

        .ttd-space {
            height: 80px;
        }

        .footer {
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            font-size: 10pt;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Kop Surat --}}
        <div class="kop-surat">
            @if(file_exists(public_path('images/kop_surat.png')))
                <img src="{{ public_path('images/kop_surat.png') }}" alt="Kop Surat">
            @else
                <h2>KEMENTERIAN PERHUBUNGAN</h2>
                <h3>PUSAT PEMBINAAN JABATAN FUNGSIONAL TRANSPORTASI</h3>
                <p>Jalan Medan Merdeka Barat No. 8, Jakarta 10110</p>
                <p>Telp: (021) 3500000, Fax: (021) 3500001</p>
                <p>Email: pusbin@dephub.go.id, Website: www.pusbin.dephub.go.id</p>
            @endif
        </div>

        {{-- Judul Surat --}}
        <div class="judul-surat">
            <h1>SURAT PERTIMBANGAN PENGANGKATAN<br>JABATAN FUNGSIONAL TRANSPORTASI</h1>
        </div>

        {{-- Nomor Surat --}}
        @if($permohonan->surat->first() && $permohonan->surat->first()->nomor_surat)
        <div class="nomor-surat">
            <p>Nomor: {{ $permohonan->surat->first()->nomor_surat }}</p>
        </div>
        @endif

        {{-- Isi Surat --}}
        <div class="isi-surat">
            <p class="salam">Yang terhormat,</p>

            <p>Kepala {{ $permohonan->unitKerja->nama_rumahsakit }}</p>

            <p>Di Tempat</p>

            <p>Perihal: Pertimbangan Pengangkatan Jabatan Fungsional Transportasi</p>

            <p>&nbsp;</p>

            <p>Sebagai tindak lanjut dari permohonan Nomor: {{ $permohonan->nomor_permohonan }} tanggal {{ $permohonan->tanggal_permohonan->format('d F Y') }}, dengan ini disampaikan pertimbangan pengangkatan Jabatan Fungsional Transportasi di lingkungan {{ $permohonan->unitKerja->nama_rumahsakit }} dengan rincian sebagai berikut:</p>

            <p><strong>Jalur Pengangkatan:</strong> {{ $permohonan->jalur_label }}</p>

            {{-- Tabel Peserta --}}
            <table class="tabel-peserta">
                <thead>
                    <tr>
                        <th class="no">No</th>
                        <th>Nama</th>
                        <th>NIP</th>
                        <th>Jabatan Asal</th>
                        <th>Jenjang Asal</th>
                        <th>Jabatan Tujuan</th>
                        <th>Jenjang Tujuan</th>
                        <th>Unit Kerja Tujuan</th>
                        <th>Hasil Ujikom</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permohonan->peserta as $index => $peserta)
                    <tr>
                        <td class="no">{{ $index + 1 }}</td>
                        <td>{{ $peserta->pegawai->nama_lengkap }}</td>
                        <td>{{ $peserta->pegawai->nip ?? '-' }}</td>
                        <td>{{ $peserta->jabatan_asal ?? '-' }}</td>
                        <td>{{ $peserta->jenjang_asal ?? '-' }}</td>
                        <td>{{ $peserta->jabatanTujuan?->nama_formasi ?? '-' }}</td>
                        <td>{{ $peserta->jenjang_tujuan ?? '-' }}</td>
                        <td>{{ $peserta->unitKerjaTujuan?->nama_rumahsakit ?? '-' }}</td>
                        <td>
                            @if($peserta->ujikomPeserta)
                                {{ strtoupper($peserta->ujikomPeserta->hasil) }}
                            @else
                                <em>Belum mengikuti</em>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Catatan Khusus untuk peserta dengan formasi tidak tersedia --}}
            @if($permohonan->peserta->contains('status_validasi_formasi', 'tidak_tersedia'))
            <div class="catatan-khusus">
                <p><strong>Catatan:</strong></p>
                <p>*) Peserta dengan tanda * ditunjukkan untuk formasi yang sudah penuh/over kuota. Keputusan pengangkatan tetap dapat dilaksanakan dengan mempertimbangkan kebutuhan organisasi dan persetujuan pejabat berwenang.</p>
            </div>
            @endif

            {{-- Penutup --}}
            <div class="penutup">
                <p>Demikian surat pertimbangan ini disampaikan untuk dapat dipergunakan sebagaimana mestinya.</p>

                <p class="salam">Hormat kami,</p>
            </div>

            {{-- Tanda Tangan --}}
            <div class="ttd-container">
                <div class="ttd-box">
                    <p>Verifikator,</p>
                    <div class="ttd-space"></div>
                    <p><strong>[Nama Verifikator]</strong></p>
                    <p>NIP. [NIP Verifikator]</p>
                </div>
                <div class="ttd-box">
                    <p>Kepala Pusat Pembinaan JFT,</p>
                    <div class="ttd-space"></div>
                    <p><strong>[Nama Kepala Pusat]</strong></p>
                    <p>NIP. [NIP Kepala Pusat]</p>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>Surat ini dibuat secara elektronik dan sah tanpa tanda tangan basah</p>
            <p>Tanggal Dibuat: {{ now()->format('d F Y H:i') }} WIB</p>
        </div>
    </div>
</body>
</html>
