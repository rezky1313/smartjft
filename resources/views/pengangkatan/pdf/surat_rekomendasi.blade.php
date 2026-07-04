<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Surat Rekomendasi Pengangkatan — {{ $permohonan->kode_permohonan }}</title>
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
    table.data td { border: 1px solid #ccc; padding: 4px 6px; font-size: 10px; vertical-align: top; }
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

  <div class="judul">SURAT REKOMENDASI PENGANGKATAN<br>JABATAN FUNGSIONAL TRANSPORTASI</div>

  @if ($permohonan->surat?->nomor_surat)
  <div class="nomor-surat">Nomor: {{ $permohonan->surat->nomor_surat }}</div>
  @endif

  {{-- Paragraf Pembuka --}}
  <p>
    Yang bertanda tangan di bawah ini, Kepala Pusat Pembinaan Jabatan Fungsional Transportasi,
    dengan ini menerangkan bahwa berdasarkan hasil Uji Kompetensi Jabatan Fungsional Transportasi
    dan ketersediaan formasi jabatan pada <strong>{{ $permohonan->unitKerja?->nama_rumahsakit ?? '-' }}</strong>,
    merekomendasikan pengangkatan nama-nama berikut ke dalam jabatan fungsional yang tertera di bawah ini:
  </p>

  {{-- Tabel Kandidat Direkomendasikan --}}
  <table class="data">
    <thead>
      <tr>
        <th width="25">No</th>
        <th>Nama Pegawai</th>
        <th width="100">NIP</th>
        <th width="90">Jabatan Asal</th>
        <th width="80">Jenjang Asal</th>
        <th width="90">Jabatan Tujuan</th>
        <th width="80">Jenjang Tujuan</th>
        <th width="45" class="text-center">Nilai</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($kandidatDirekomendasikan as $i => $k)
      <tr>
        <td class="text-center">{{ $i + 1 }}</td>
        <td>{{ $k->pegawai?->nama_lengkap ?? '-' }}</td>
        <td>{{ $k->pegawai?->nip ?? '-' }}</td>
        <td>{{ $k->jabatan_asal }}</td>
        <td>{{ $k->jenjang_asal }}</td>
        <td>{{ $k->jabatanTujuan?->nama_formasi ?? '-' }}</td>
        <td>{{ $k->jenjang_tujuan }}</td>
        <td class="text-center">{{ number_format($k->nilai_ujikom, 1) }}</td>
      </tr>
      @empty
      <tr><td colspan="8" class="text-center">Tidak ada kandidat yang direkomendasikan.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- Paragraf Penutup --}}
  <p>
    Demikian surat rekomendasi ini dibuat untuk dapat dipergunakan sebagaimana mestinya.
    Pengangkatan dalam jabatan fungsional ini dilaksanakan sesuai dengan ketentuan peraturan
    perundang-undangan yang berlaku.
  </p>

  {{-- TTD --}}
  <div class="ttd-area">
    <table>
      <tr>
        <td width="60%"></td>
        <td>
          Jakarta, {{ $permohonan->surat?->tanggal_surat?->format('d F Y') ?? now()->format('d F Y') }}<br><br>
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
