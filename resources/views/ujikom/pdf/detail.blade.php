<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Permohonan Uji Kompetensi - {{ $permohonan->nomor_permohonan }}</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 11px;
      line-height: 1.4;
      margin: 15px;
    }
    .kop-surat {
      text-align: center;
      margin-bottom: 15px;
    }
    .kop-surat img {
      width: 100%;
      max-height: 80px;
    }
    .judul {
      text-align: center;
      font-size: 14px;
      font-weight: bold;
      margin-bottom: 15px;
    }
    .info-box {
      border: 1px solid #333;
      padding: 10px;
      margin-bottom: 15px;
    }
    .info-box table {
      border: none;
      margin-bottom: 0;
    }
    .info-box td {
      border: none;
      padding: 3px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }
    table th,
    table td {
      border: 1px solid #333;
      padding: 6px;
      text-align: left;
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
    .badge-success {
      background-color: #28a745;
      color: white;
      padding: 3px 8px;
      border-radius: 3px;
      font-size: 9px;
      display: inline-block;
    }
    .badge-danger {
      background-color: #dc3545;
      color: white;
      padding: 3px 8px;
      border-radius: 3px;
      font-size: 9px;
      display: inline-block;
    }
    .badge-secondary {
      background-color: #6c757d;
      color: white;
      padding: 3px 8px;
      border-radius: 3px;
      font-size: 9px;
      display: inline-block;
    }
    .footer {
      margin-top: 30px;
      text-align: right;
      font-size: 10px;
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
  <div class="judul">DETAIL PERMOHONAN UJI KOMPETENSI</div>

  {{-- Informasi Permohonan --}}
  <div class="info-box">
    <table>
      <tr>
        <td width="200"><strong>Nomor Permohonan</strong></td>
        <td>: {{ $permohonan->nomor_permohonan }}</td>
      </tr>
      <tr>
        <td><strong>Unit Kerja</strong></td>
        <td>: {{ $permohonan->unitKerja->nama_rumahsakit }}</td>
      </tr>
      <tr>
        <td><strong>Alamat</strong></td>
        <td>: {{ $permohonan->unitKerja->alamat ?? '-' }}</td>
      </tr>
      <tr>
        <td><strong>Kabupaten/Kota</strong></td>
        <td>: {{ $permohonan->unitKerja->regency->type }} {{ $permohonan->unitKerja->regency->name }}</td>
      </tr>
      <tr>
        <td><strong>Provinsi</strong></td>
        <td>: {{ $permohonan->unitKerja->regency->province->name }}</td>
      </tr>
      <tr>
        <td><strong>Tanggal Permohonan</strong></td>
        <td>: {{ $permohonan->tanggal_permohonan->format('d/m/Y') }}</td>
      </tr>
      <tr>
        <td><strong>Status</strong></td>
        <td>: {{ $permohonan->status_label }}</td>
      </tr>
      @if($permohonan->tanggal_jadwal)
        <tr>
          <td><strong>Tanggal Jadwal</strong></td>
          <td>: {{ $permohonan->tanggal_jadwal->format('d/m/Y') }}</td>
        </tr>
      @endif
      @if($permohonan->tempat_ujikom)
        <tr>
          <td><strong>Tempat Uji Kompetensi</strong></td>
          <td>: {{ $permohonan->tempat_ujikom }}</td>
        </tr>
      @endif
      @if($permohonan->catatan_verifikator)
        <tr>
          <td><strong>Catatan Verifikator</strong></td>
          <td>: {{ $permohonan->catatan_verifikator }}</td>
        </tr>
      @endif
      <tr>
        <td><strong>Dibuat Oleh</strong></td>
        <td>: {{ $permohonan->createdBy->name ?? '-' }}</td>
      </tr>
      <tr>
        <td><strong>Tanggal Dibuat</strong></td>
        <td>: {{ $permohonan->created_at->format('d/m/Y H:i') }}</td>
      </tr>
    </table>
  </div>

  {{-- Daftar Peserta --}}
  <h3>Daftar Peserta ({{ $permohonan->peserta->count() }})</h3>
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
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $peserta->pegawai->nama_lengkap }}</td>
          <td>{{ $peserta->pegawai->nip ?? '-' }}</td>
          <td>{{ $peserta->pegawai->formasi->nama_formasi ?? '-' }}</td>
          <td>{{ $peserta->pegawai->formasi->jenjang->nama_jenjang ?? '-' }}</td>
          <td>
            @if($peserta->hasil === 'lulus')
              <span class="badge-success">LULUS</span>
            @elseif($peserta->hasil === 'tidak_lulus')
              <span class="badge-danger">TIDAK LULUS</span>
            @else
              <span class="badge-secondary">BELUM</span>
            @endif
          </td>
          <td>{{ $peserta->catatan_hasil ?? '-' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @if($permohonan->status === 'selesai')
    @php
      $jumlahLulus = $permohonan->peserta->where('hasil', 'lulus')->count();
      $jumlahTidakLulus = $permohonan->peserta->where('hasil', 'tidak_lulus')->count();
    @endphp
    <div class="info-box">
      <strong>Ringkasan Hasil:</strong><br>
      Total Peserta: {{ $permohonan->peserta->count() }}<br>
      Lulus: {{ $jumlahLulus }} orang<br>
      Tidak Lulus: {{ $jumlahTidakLulus }} orang
    </div>
  @endif

  <div class="footer">
    <p>Dicetak pada: {{ date('d/m/Y H:i') }}</p>
    <p>Sistem Manajemen Jabatan Fungsional Transportasi (SMART JFT)<br>
    Pusat Pembinaan Jabatan Fungsional Transportasi</p>
  </div>
</body>
</html>
