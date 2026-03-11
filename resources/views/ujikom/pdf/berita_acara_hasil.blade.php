<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Berita Acara Hasil - {{ $permohonan->nomor_permohonan }}</title>
  <style>
    body {
      font-family: Times New Roman, serif;
      font-size: 12px;
      line-height: 1.6;
      margin: 20px;
    }
    .kop-surat {
      text-align: center;
      margin-bottom: 20px;
    }
    .kop-surat img {
      width: 100%;
      max-height: 100px;
    }
    .judul {
      text-align: center;
      font-size: 14px;
      font-weight: bold;
      margin-bottom: 5px;
      text-decoration: underline;
    }
    .nomor-ba {
      text-align: center;
      font-size: 12px;
      margin-bottom: 20px;
    }
    .content {
      text-align: justify;
      margin-bottom: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    table th,
    table td {
      border: 1px solid #000;
      padding: 8px;
      text-align: left;
      vertical-align: top;
    }
    table th {
      background-color: #f0f0f0;
      text-align: center;
      font-weight: bold;
    }
    table td:first-child {
      text-align: center;
      width: 30px;
    }
    table td:nth-child(6) {
      text-align: center;
    }
    .info-box {
      border: 1px solid #000;
      padding: 10px;
      margin-bottom: 20px;
    }
    .info-box table {
      border: none;
      margin-bottom: 0;
    }
    .info-box td {
      border: none;
      padding: 2px;
    }
    .signature {
      margin-top: 50px;
    }
    .signature-table {
      width: 100%;
      border: none;
    }
    .signature-table td {
      border: none;
      padding: 5px;
      text-align: center;
      vertical-align: top;
    }
    .signature-space {
      height: 80px;
    }
    .lulus {
      background-color: #d4edda;
    }
    .tidak-lulus {
      background-color: #f8d7da;
    }
  </style>
</head>
<body>
  {{-- Kop Surat --}}
  <div class="kop-surat">
    @if(file_exists(public_path('images/kop_surat.png')))
      <img src="{{ asset('images/kop_surat.png') }}" alt="Kop Surat">
    @endif
  </div>

  {{-- Judul --}}
  <div class="judul">BERITA ACARA HASIL UJI KOMPETENSI</div>
  <div class="nomor-ba">Nomor: {{ $permohonan->nomor_permohonan }}/BA-H</div>

  {{-- Isi --}}
  <div class="content">
    <p>Yang bertanda tangan di bawah ini menyatakan bahwa:</p>

    <div class="info-box">
      <table>
        <tr>
          <td width="200">Unit Kerja</td>
          <td>: {{ $permohonan->unitKerja->nama_rumahsakit }}</td>
        </tr>
        <tr>
          <td>Kabupaten/Kota</td>
          <td>: {{ $permohonan->unitKerja->regency->type }} {{ $permohonan->unitKerja->regency->name }}</td>
        </tr>
        <tr>
          <td>Provinsi</td>
          <td>: {{ $permohonan->unitKerja->regency->province->name }}</td>
        </tr>
        <tr>
          <td>Nomor Permohonan</td>
          <td>: {{ $permohonan->nomor_permohonan }}</td>
        </tr>
        <tr>
          <td>Hari, Tanggal</td>
          <td>: {{ $permohonan->tanggal_jadwal?->format('l, d/m/Y') }}</td>
        </tr>
        <tr>
          <td>Tempat</td>
          <td>: {{ $permohonan->tempat_ujikom }}</td>
        </tr>
      </table>
    </div>

    <p>
      Telah melaksanakan uji kompetensi Jabatan Fungsional Transportasi dan dengan ini menyatakan hasil uji kompetensi
      sebagai berikut:
    </p>

    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Pegawai</th>
          <th>NIP</th>
          <th>Jabatan Fungsional</th>
          <th>Jenjang</th>
          <th>Hasil</th>
          <th>Catatan</th>
        </tr>
      </thead>
      <tbody>
        @foreach($permohonan->peserta as $i => $peserta)
          <tr class="{{ $peserta->hasil === 'lulus' ? 'lulus' : ($peserta->hasil === 'tidak_lulus' ? 'tidak-lulus' : '') }}">
            <td>{{ $i + 1 }}</td>
            <td>{{ $peserta->pegawai->nama_lengkap }}</td>
            <td>{{ $peserta->pegawai->nip ?? '-' }}</td>
            <td>{{ $peserta->pegawai->formasi->nama_formasi ?? '-' }}</td>
            <td>{{ $peserta->pegawai->formasi->jenjang->nama_jenjang ?? '-' }}</td>
            <td>
              <strong>
                @if($peserta->hasil === 'lulus')
                  LULUS
                @elseif($peserta->hasil === 'tidak_lulus')
                  TIDAK LULUS
                @else
                  -
                @endif
              </strong>
            </td>
            <td>{{ $peserta->catatan_hasil ?? '-' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    @php
      $jumlahLulus = $permohonan->peserta->where('hasil', 'lulus')->count();
      $jumlahTidakLulus = $permohonan->peserta->where('hasil', 'tidak_lulus')->count();
      $jumlahTotal = $permohonan->peserta->count();
    @endphp

    <p>
      Dari {{ $jumlahTotal }} peserta uji kompetensi, dinyatakan:
      <strong>{{ $jumlahLulus }} orang LULUS</strong> dan
      <strong>{{ $jumlahTidakLulus }} orang TIDAK LULUS</strong>.
    </p>

    <p>Demikian berita acara hasil uji kompetensi ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
  </div>

  {{-- Tanda Tangan --}}
  <div class="signature">
    <table class="signature-table">
      <tr>
        <td width="60%"></td>
        <td width="40%">
          <div>Mengetahui,</div>
          <div class="signature-space"></div>
          <div><strong>Verifikator</strong></div>
          <div>NIP. __________________</div>
        </td>
      </tr>
    </table>
  </div>

  <div style="margin-top: 30px;">
    <table class="signature-table">
      <tr>
        <td width="60%"></td>
        <td width="40%">
          <div>{{ date('d F Y') }}</div>
          <div><strong>Kepala Pusat</strong></div>
          <div>Pembinaan Jabatan Fungsional Transportasi</div>
          <div class="signature-space"></div>
          <div><strong>Nama Kepala Pusat</strong></div>
          <div>NIP. __________________</div>
        </td>
      </tr>
    </table>
  </div>
</body>
</html>
