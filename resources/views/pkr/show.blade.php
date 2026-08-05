@extends('layouts.users.master')
@section('title', 'Pengembangan Karir JFT')
@section('isi')

<div class="preview-card">
  <div class="preview-header">
    <span class="preview-header-title">Pengembangan Karir JFT</span>
    <span class="preview-header-subtitle d-block">{{ $sdm->nama_lengkap }} — NIP {{ $sdm->nip ?? '-' }}</span>
  </div>
  <div class="preview-body">

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <span class="subsection-label">Ringkasan</span>
    <div class="form-row">
      <div class="form-group col-md-3">
        <label class="font-weight-bold">Jenjang Saat Ini</label>
        <p class="mb-0">{{ $sdm->jenjang_nama ?? '—' }}</p>
      </div>
      <div class="form-group col-md-3">
        <label class="font-weight-bold">Unit Kerja</label>
        <p class="mb-0">{{ $sdm->unitKerja->nama_unit_kerja ?? '—' }}</p>
      </div>
      <div class="form-group col-md-3">
        <label class="font-weight-bold">Angka Kredit Kumulatif</label>
        <p class="mb-0"><strong>{{ number_format($akKumulatif, 4) }}</strong></p>
      </div>
      <div class="form-group col-md-3">
        <label class="font-weight-bold">Ambang Batas Jenjang Berikutnya</label>
        @if($ambangBatas)
          <p class="mb-0">
            Naik ke <strong>{{ ucwords(str_replace('_', ' ', $ambangBatas['ke_jenjang'])) }}</strong>
            butuh <strong>{{ number_format($ambangBatas['ak_kumulatif_minimal'], 2) }}</strong> AK
            (kurang {{ number_format(max(0, $ambangBatas['ak_kumulatif_minimal'] - $akKumulatif), 4) }})
          </p>
        @else
          <p class="mb-0 text-muted">Sudah di jenjang tertinggi / data jenjang tidak dikenali.</p>
        @endif
      </div>
    </div>

    <hr class="my-4">

    <span class="subsection-label">Input Riwayat Angka Kredit</span>
    <form action="{{ route('user.pkr.angka-kredit.store', $sdm->id) }}" method="POST" id="formAngkaKredit">
      @csrf
      <div class="form-row">
        <div class="form-group col-md-3">
          <label class="font-weight-bold">Periode Penilaian <span class="text-danger">*</span></label>
          <input type="text" name="periode_bulan" class="form-control" placeholder="mis. Januari - Maret 2023" required>
          @error('periode_bulan') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="form-group col-md-2">
          <label class="font-weight-bold">Tahun</label>
          <input type="number" name="tahun" class="form-control" value="{{ date('Y') }}" min="2000" max="2100">
        </div>

        <div class="form-group col-md-2">
          <label class="font-weight-bold">Jumlah Bulan <span class="text-danger">*</span></label>
          <input type="number" name="jumlah_bulan" id="jumlahBulan" class="form-control" min="1" max="12" required>
          @error('jumlah_bulan') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="form-group col-md-3">
          <label class="font-weight-bold">Predikat Kinerja <span class="text-danger">*</span></label>
          <select name="predikat_kinerja" id="predikatKinerja" class="form-control" required>
            <option value="">-- Pilih Predikat --</option>
            @foreach($daftarPredikat as $p)
              <option value="{{ $p->predikat }}" data-persentase="{{ $p->persentase }}">
                {{ ucwords(str_replace('_', ' ', $p->predikat)) }} ({{ (int) $p->persentase }}%)
              </option>
            @endforeach
          </select>
          @error('predikat_kinerja') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="form-group col-md-2">
          <label class="font-weight-bold">Perkiraan AK</label>
          <input type="text" id="perkiraanAk" class="form-control" readonly value="0.0000">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-12">
          <label class="font-weight-bold">Catatan</label>
          <textarea name="catatan" class="form-control" rows="2"></textarea>
        </div>
      </div>

      @if(!$koefisienJenjang)
        <div class="alert alert-warning">Koefisien untuk jenjang pegawai ini belum tersedia di referensi — perhitungan tidak bisa dilakukan.</div>
      @endif

      <div class="d-flex justify-content-end mt-3">
        <button type="submit" class="btn btn-primary" {{ $koefisienJenjang ? '' : 'disabled' }}>
          <i class="fas fa-save"></i> Simpan Angka Kredit
        </button>
      </div>
    </form>

    <hr class="my-4">

    <span class="subsection-label">Riwayat Angka Kredit</span>
    <div class="table-responsive">
      <table class="table table-bordered table-sm">
        <thead>
          <tr>
            <th>Tahun</th>
            <th>Periode</th>
            <th>Jumlah Bulan</th>
            <th>Predikat</th>
            <th>Jenjang Saat Itu</th>
            <th>AK Diperoleh</th>
            <th>Dinilai Oleh</th>
            <th>Catatan</th>
          </tr>
        </thead>
        <tbody>
          @forelse($riwayat as $r)
            <tr>
              <td>{{ $r->tahun }}</td>
              <td>{{ $r->periode_bulan }}</td>
              <td>{{ $r->jumlah_bulan }}</td>
              <td>{{ ucwords(str_replace('_', ' ', $r->predikat_kinerja)) }}</td>
              <td>{{ $r->jenjang_saat_itu }}</td>
              <td>{{ number_format($r->angka_kredit_diperoleh, 4) }}</td>
              <td>{{ $r->penilai->name ?? '-' }}</td>
              <td>{{ $r->catatan }}</td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted">Belum ada riwayat angka kredit.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const koefisien = {{ $koefisienJenjang ?? 0 }};
  const jumlahBulan = document.getElementById('jumlahBulan');
  const predikat = document.getElementById('predikatKinerja');
  const preview = document.getElementById('perkiraanAk');

  function hitungPreview() {
    const bulan = parseFloat(jumlahBulan.value) || 0;
    const opt = predikat.options[predikat.selectedIndex];
    const persentase = opt ? parseFloat(opt.dataset.persentase) || 0 : 0;

    const ak = (bulan / 12) * (persentase / 100) * koefisien;
    preview.value = ak.toFixed(4);
  }

  jumlahBulan?.addEventListener('input', hitungPreview);
  predikat?.addEventListener('change', hitungPreview);
});
</script>
@endpush
@endsection
