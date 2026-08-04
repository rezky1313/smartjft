<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Berita Acara Verifikasi Formasi PKB — {{ $ba->nomor_ba }}</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 11px; line-height: 1.5; margin: 15px 25px; }
    .kop-surat { text-align: center; margin-bottom: 10px; }
    .kop-surat img { width: 100%; max-height: 75px; }
    .judul { text-align: center; font-size: 13px; font-weight: bold; margin: 15px 0 5px; text-transform: uppercase; }
    .nomor-surat { text-align: center; font-size: 11px; margin-bottom: 20px; }
    p { text-align: justify; margin: 8px 0; }
    .rincian { margin: 10px 0 10px 20px; }
    .rincian td { padding: 3px 6px; vertical-align: top; }
    .text-center { text-align: center; }
    .ttd-area { margin-top: 30px; width: 100%; }
    .ttd-area td { text-align: center; padding: 8px; vertical-align: top; width: 50%; }
    hr { border-top: 2px solid #333; margin: 8px 0; }
  </style>
</head>
<body>

  {{-- Kop Surat --}}
  <div class="kop-surat">
    @if(file_exists(public_path('images/kop_surat.png')))
    <img src="{{ public_path('images/kop_surat.png') }}" alt="Kop Surat">
    @else
    <p><strong>PUSAT PEMBINAAN JABATAN FUNGSIONAL TRANSPORTASI</strong><br>
    Kementerian Perhubungan Republik Indonesia</p>
    @endif
  </div>
  <hr>

  <div class="judul">BERITA ACARA HASIL VERIFIKASI PENGHITUNGAN FORMASI<br>JABATAN FUNGSIONAL PENGUJI KENDARAAN BERMOTOR</div>

  @if ($ba->nomor_ba)
  <div class="nomor-surat">Nomor: {{ $ba->nomor_ba }}</div>
  @endif

  <p>
    Pada hari ini, {{ $ba->tanggal_verifikasi?->format('d F Y') ?? now()->format('d F Y') }}, telah dilaksanakan verifikasi
    penghitungan Formasi Jabatan Fungsional Penguji Kendaraan Bermotor dengan hasil sebagai berikut:
  </p>

  <p>I. Nama Instansi: <strong>{{ $usulan->unitKerja?->nama_unit_kerja ?? '-' }}</strong></p>

  <p>II. Hasil Verifikasi Penghitungan Formasi:</p>

  <table class="rincian">
    @php
      $urutJenjang = ['pemula' => 1, 'terampil' => 2, 'mahir' => 3, 'penyelia' => 4];
      $hasilTerurut = $usulan->hasil->sortBy(fn($h) => $urutJenjang[$h->jenjang] ?? 99);
    @endphp
    @foreach ($hasilTerurut as $h)
    <tr>
      <td width="20">{{ $urutJenjang[$h->jenjang] ?? '-' }}.</td>
      <td>Penguji Kendaraan Bermotor {{ ucfirst($h->jenjang) }}</td>
      <td width="10">:</td>
      <td width="80"><strong>{{ $h->formasi_final }}</strong> orang</td>
    </tr>
    @endforeach
  </table>

  <p>
    Demikian Berita Acara ini dibuat dan ditandatangani oleh kedua belah pihak.
  </p>

  {{-- TTD --}}
  <table class="ttd-area">
    <tr>
      <td>
        Kepala Bidang Perencanaan dan Pembentukan JFT<br>
        Pusat Pembinaan JFT<br><br><br>
        @if ($ba->ttd_pusbin_oleh)
          <strong>{{ $ba->ttdPusbinOleh?->name ?? '-' }}</strong><br>
          <small>Ditandatangani digital, {{ $ba->ttd_pusbin_at?->format('d F Y H:i') }}</small>
        @else
          <div style="width:180px; border-bottom:1px solid #333; margin:20px auto 0;"></div>
          <br>NIP. ................................
        @endif
      </td>
      <td>
        Kepala Kantor/Dishub Pengusul<br>
        {{ $usulan->unitKerja?->nama_unit_kerja ?? '-' }}<br><br><br>
        @if ($ba->ttd_pengusul_oleh)
          <strong>{{ $ba->ttdPengusulOleh?->name ?? '-' }}</strong><br>
          <small>Ditandatangani digital, {{ $ba->ttd_pengusul_at?->format('d F Y H:i') }}</small>
        @else
          <div style="width:180px; border-bottom:1px solid #333; margin:20px auto 0;"></div>
          <br>NIP. ................................
        @endif
      </td>
    </tr>
  </table>

</body>
</html>
