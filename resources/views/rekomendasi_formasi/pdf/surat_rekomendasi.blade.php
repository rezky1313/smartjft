<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Surat Rekomendasi Formasi PKB — {{ $usulan->unitKerja?->nama_unit_kerja ?? $usulan->id }}</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 11px; line-height: 1.5; margin: 15px 25px; }
    .kop-surat { text-align: center; margin-bottom: 10px; }
    .kop-surat img { width: 100%; max-height: 75px; }
    .judul { text-align: center; font-size: 13px; font-weight: bold; margin: 15px 0 5px; text-transform: uppercase; }
    .sub-judul { text-align: center; font-size: 11px; margin-bottom: 15px; }
    .nomor-surat { text-align: center; font-size: 11px; margin-bottom: 20px; }
    p { text-align: justify; margin: 8px 0; }
    table.data { width: 100%; border-collapse: collapse; margin: 12px 0; }
    table.data th { background: #1a3c5e; color: #fff; padding: 5px 6px; text-align: center; font-size: 10px; }
    table.data td { border: 1px solid #ccc; padding: 4px 6px; font-size: 10px; text-align: center; }
    table.data tr:nth-child(even) td { background: #f5f5f5; }
    .text-center { text-align: center; }
    .ttd-area { margin-top: 30px; }
    .ttd-area table { width: 100%; }
    .ttd-area td { text-align: center; padding: 8px; vertical-align: top; }
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

  <div class="judul">SURAT REKOMENDASI FORMASI<br>JABATAN FUNGSIONAL PENGUJI KENDARAAN BERMOTOR</div>

  @if ($usulan->surat?->nomor_surat)
  <div class="nomor-surat">Nomor: {{ $usulan->surat->nomor_surat }}</div>
  @endif

  <p>
    Yang bertanda tangan di bawah ini, Kepala Pusat Pembinaan Jabatan Fungsional Transportasi,
    dengan ini menerangkan bahwa berdasarkan hasil verifikasi penghitungan Formasi Jabatan Fungsional
    Penguji Kendaraan Bermotor tahun {{ $usulan->tahun }} pada <strong>{{ $usulan->unitKerja?->nama_unit_kerja ?? '-' }}</strong>
    yang telah dituangkan dalam Berita Acara Nomor {{ $usulan->beritaAcara?->nomor_ba ?? '-' }},
    merekomendasikan penambahan Formasi Jabatan Fungsional Penguji Kendaraan Bermotor sebagai berikut:
  </p>

  <table class="data">
    <thead>
      <tr>
        <th width="40">No</th>
        <th>Jenjang</th>
        <th width="120">Rekomendasi Formasi</th>
      </tr>
    </thead>
    <tbody>
      @php
        $urutJenjang = ['pemula' => 1, 'terampil' => 2, 'mahir' => 3, 'penyelia' => 4];
        $hasilTerurut = $usulan->hasil->sortBy(fn($h) => $urutJenjang[$h->jenjang] ?? 99);
      @endphp
      @foreach ($hasilTerurut as $h)
      <tr>
        <td>{{ $urutJenjang[$h->jenjang] ?? '-' }}</td>
        <td>Penguji Kendaraan Bermotor {{ ucfirst($h->jenjang) }}</td>
        <td><strong>{{ $h->formasi_final }}</strong> orang</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <p>
    Demikian surat rekomendasi ini dibuat untuk dapat dipergunakan sebagaimana mestinya sebagai dasar
    penambahan Formasi Jabatan Fungsional Penguji Kendaraan Bermotor sesuai dengan ketentuan peraturan
    perundang-undangan yang berlaku.
  </p>

  {{-- TTD --}}
  <div class="ttd-area">
    <table>
      <tr>
        <td width="60%"></td>
        <td>
          Jakarta, {{ $usulan->surat?->tanggal_surat?->format('d F Y') ?? now()->format('d F Y') }}<br><br>
          Kepala Pusat Pembinaan<br>
          Jabatan Fungsional Transportasi<br><br><br><br><br>
          <div style="width:180px; border-bottom:1px solid #333; margin:0 auto;"></div>
          <br>NIP. ................................
        </td>
      </tr>
    </table>
  </div>

</body>
</html>
