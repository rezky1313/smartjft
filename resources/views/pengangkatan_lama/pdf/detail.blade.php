<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Detail Permohonan Pengangkatan</title>
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

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 5px 0;
        }

        .header h2 {
            font-size: 12pt;
            font-weight: bold;
            margin: 10px 0 5px 0;
        }

        .info-box {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #000;
        }

        .info-box p {
            margin: 5px 0;
        }

        .info-box strong {
            display: inline-block;
            width: 150px;
        }

        .section {
            margin: 25px 0;
        }

        .section h3 {
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }

        .tabel-peserta {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .tabel-peserta th,
        .tabel-peserta td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-size: 10pt;
        }

        .tabel-peserta th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .tabel-peserta .no {
            text-align: center;
            width: 30px;
        }

        .tabel-peserta .center {
            text-align: center;
        }

        .badge-tersedia {
            display: inline-block;
            padding: 2px 8px;
            background-color: #28a745;
            color: white;
            font-size: 9pt;
            border-radius: 3px;
        }

        .badge-tidak-tersedia {
            display: inline-block;
            padding: 2px 8px;
            background-color: #dc3545;
            color: white;
            font-size: 9pt;
            border-radius: 3px;
        }

        .badge-memenuhi {
            display: inline-block;
            padding: 2px 8px;
            background-color: #28a745;
            color: white;
            font-size: 9pt;
            border-radius: 3px;
        }

        .badge-tidak-memenuhi {
            display: inline-block;
            padding: 2px 8px;
            background-color: #ffc107;
            color: #000;
            font-size: 9pt;
            border-radius: 3px;
        }

        .footer {
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            font-size: 10pt;
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <h1>DETAIL PERMOHONAN PERTIMBANGAN PENGANGKATAN</h1>
            <h2>JABATAN FUNGSIONAL TRANSPORTASI</h2>
        </div>

        {{-- Informasi Permohonan --}}
        <div class="section">
            <h3>INFORMASI PERMOHONAN</h3>
            <div class="info-box">
                <p><strong>Nomor Permohonan:</strong> {{ $permohonan->nomor_permohonan }}</p>
                <p><strong>Jalur:</strong> {{ $permohonan->jalur_label }}</p>
                <p><strong>Unit Kerja:</strong> {{ $permohonan->unitKerja->nama_rumahsakit }}</p>
                <p><strong>Provinsi:</strong> {{ $permohonan->unitKerja->regency->province->nama_provinsi ?? '-' }}</p>
                <p><strong>Kabupaten/Kota:</strong> {{ $permohonan->unitKerja->regency->nama_kabupaten ?? '-' }}</p>
                <p><strong>Tanggal Permohonan:</strong> {{ $permohonan->tanggal_permohonan->format('d F Y') }}</p>
                <p><strong>Status:</strong> {{ $permohonan->status_label }}</p>
                <p><strong>Diajukan Oleh:</strong> {{ $permohonan->createdBy->name ?? '-' }}</p>
                @if($permohonan->catatan_verifikator)
                <p><strong>Catatan Verifikator:</strong> {{ $permohonan->catatan_verifikator }}</p>
                @endif
            </div>
        </div>

        {{-- Daftar Peserta --}}
        <div class="section">
            <h3>DAFTAR PESERTA ({{ $permohonan->peserta->count() }})</h3>
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
                        <th class="center">Formasi</th>
                        <th class="center">Ujikom</th>
                        <th>Catatan</th>
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
                        <td class="center">
                            @if($peserta->status_validasi_formasi === 'tersedia')
                                <span class="badge-tersedia">Tersedia</span>
                            @else
                                <span class="badge-tidak-tersedia">Penuh</span>
                            @endif
                        </td>
                        <td class="center">
                            @if($peserta->status_validasi_ujikom === 'memenuhi')
                                <span class="badge-memenuhi">Memenuhi</span>
                            @else
                                <span class="badge-tidak-memenuhi">Belum</span>
                            @endif
                            @if($peserta->ujikomPeserta)
                                <br><small>{{ $peserta->ujikomPeserta->hasil }}</small>
                            @endif
                        </td>
                        <td>{{ $peserta->catatan ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Ringkasan Validasi --}}
        <div class="section">
            <h3>RINGKASAN VALIDASI</h3>
            <div class="info-box">
                <p><strong>Total Peserta:</strong> {{ $permohonan->peserta->count() }}</p>
                <p><strong>Formasi Tersedia:</strong> {{ $permohonan->peserta->where('status_validasi_formasi', 'tersedia')->count() }}</p>
                <p><strong>Formasi Penuh:</strong> {{ $permohonan->peserta->where('status_validasi_formasi', 'tidak_tersedia')->count() }}</p>
                <p><strong>Ujikom Memenuhi:</strong> {{ $permohonan->peserta->where('status_validasi_ujikom', 'memenuhi')->count() }}</p>
                <p><strong>Ujikom Belum:</strong> {{ $permohonan->peserta->where('status_validasi_ujikom', 'tidak_memenuhi')->count() }}</p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>Dokumen ini dicetak secara elektronik pada: {{ now()->format('d F Y H:i') }} WIB</p>
            <p>Sumber: SMART JFT - Sistem Manajemen Jabatan Fungsional Transportasi</p>
        </div>
    </div>
</body>
</html>
