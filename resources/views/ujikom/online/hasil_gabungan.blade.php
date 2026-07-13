@extends('layouts.users.master')

@section('title', 'Hasil Ujian — Pusbin JFT')

@section('isi')
<div class="row justify-content-center">
  <div class="col-lg-9">

    @if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="fas fa-clipboard-check mr-2"></i>Hasil Ujian — 2 Sesi CAT</h3>
        <a href="{{ route('ujikom-online.index') }}" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <h5 class="mb-0">{{ $peserta->pegawai?->nama_lengkap ?? '-' }}</h5>
          <p class="text-muted mb-0 small">{{ $peserta->pegawai?->nip ?? '-' }} &mdash; {{ $jadwal->judul }}</p>
        </div>

        @if (!$hasil || $hasil->status_kelulusan === 'belum_dinilai')
        {{-- Menunggu penilaian manual --}}
        <div class="alert alert-warning">
          <i class="fas fa-hourglass-half mr-1"></i>
          <strong>Menunggu Penilaian Manual.</strong> Kedua sesi CAT sudah selesai, tapi nilai akhir belum bisa
          difinalisasi karena masih ada aspek Wawancara/Presentasi yang aktif untuk jadwal ini dan belum dinilai
          oleh Pusbin/Pewawancara.
        </div>

        <div class="row">
          <div class="col-md-6">
            <h6 class="font-weight-bold small">Sesi 1 — Teknis</h6>
            <table class="table table-sm table-bordered mb-3" style="font-size:0.85rem;">
              <tr><td class="text-muted">Nilai CAT</td><td><strong>{{ $sesiTeknis->nilai_akhir ?? '-' }}</strong></td></tr>
              @if ($bobotTeknis['wawancara'] > 0)
              <tr><td class="text-muted">Wawancara</td><td>{{ $nilaiManual->get('teknis_wawancara')?->nilai ?? '— belum dinilai —' }}</td></tr>
              @endif
              @if ($bobotTeknis['presentasi'] > 0)
              <tr><td class="text-muted">Presentasi</td><td>{{ $nilaiManual->get('teknis_presentasi')?->nilai ?? '— belum dinilai —' }}</td></tr>
              @endif
            </table>
          </div>
          <div class="col-md-6">
            <h6 class="font-weight-bold small">Sesi 2 — Mansoskul</h6>
            <table class="table table-sm table-bordered mb-3" style="font-size:0.85rem;">
              <tr><td class="text-muted">Nilai CAT</td><td><strong>{{ $sesiMansoskul->nilai_akhir ?? '-' }}</strong></td></tr>
              @if ($bobotMansoskul['wawancara'] > 0)
              <tr><td class="text-muted">Wawancara</td><td>{{ $nilaiManual->get('mansoskul_wawancara')?->nilai ?? '— belum dinilai —' }}</td></tr>
              @endif
              @if ($bobotMansoskul['presentasi'] > 0)
              <tr><td class="text-muted">Presentasi</td><td>{{ $nilaiManual->get('mansoskul_presentasi')?->nilai ?? '— belum dinilai —' }}</td></tr>
              @endif
            </table>
          </div>
        </div>

        @else
        {{-- Hasil final --}}
        <div class="text-center mb-4">
          <span class="badge badge-{{ $hasil->status_kelulusan === 'lulus' ? 'success' : 'danger' }} p-2" style="font-size:1.1rem;">
            {{ $hasil->status_kelulusan === 'lulus' ? 'LULUS' : 'TIDAK LULUS' }}
          </span>
          <h1 class="display-4 font-weight-bold mt-2 mb-0">{{ number_format($hasil->nilai, 2) }}</h1>
          <p class="text-muted mb-0">Passing Grade: {{ $hasil->passing_grade }}</p>
          @if ($hasil->status_kecurangan === 'terindikasi')
          <span class="badge badge-dark mt-2"><i class="fas fa-exclamation-triangle mr-1"></i>Terindikasi Kecurangan</span>
          @endif
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="card border">
              <div class="card-body">
                <h6 class="font-weight-bold"><i class="fas fa-toolbox mr-1 text-primary"></i>Kompetensi Teknis</h6>
                <h3 class="mb-1">{{ number_format($hasil->nilai_teknis, 2) }}</h3>
                <small class="text-muted">Bobot {{ $hasil->bobot_teknis }}% dari nilai akhir</small>
                <hr class="my-2">
                <table class="table table-sm mb-0" style="font-size:0.82rem;">
                  <tr><td class="text-muted pl-0">CAT ({{ $bobotTeknis['cat'] }}%)</td><td class="text-right pr-0">{{ $sesiTeknis->nilai_akhir ?? '-' }}</td></tr>
                  @if ($bobotTeknis['wawancara'] > 0)
                  <tr><td class="text-muted pl-0">Wawancara ({{ $bobotTeknis['wawancara'] }}%)</td><td class="text-right pr-0">{{ $nilaiManual->get('teknis_wawancara')?->nilai }}/5</td></tr>
                  @endif
                  @if ($bobotTeknis['presentasi'] > 0)
                  <tr><td class="text-muted pl-0">Presentasi ({{ $bobotTeknis['presentasi'] }}%)</td><td class="text-right pr-0">{{ $nilaiManual->get('teknis_presentasi')?->nilai }}/5</td></tr>
                  @endif
                </table>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card border">
              <div class="card-body">
                <h6 class="font-weight-bold"><i class="fas fa-users mr-1 text-success"></i>Kompetensi Mansoskul</h6>
                <h3 class="mb-1">{{ number_format($hasil->nilai_mansoskul, 2) }}</h3>
                <small class="text-muted">Bobot {{ $hasil->bobot_mansoskul }}% dari nilai akhir</small>
                <hr class="my-2">
                <table class="table table-sm mb-0" style="font-size:0.82rem;">
                  <tr><td class="text-muted pl-0">CAT ({{ $bobotMansoskul['cat'] }}%)</td><td class="text-right pr-0">{{ $sesiMansoskul->nilai_akhir ?? '-' }}</td></tr>
                  @if ($bobotMansoskul['wawancara'] > 0)
                  <tr><td class="text-muted pl-0">Wawancara ({{ $bobotMansoskul['wawancara'] }}%)</td><td class="text-right pr-0">{{ $nilaiManual->get('mansoskul_wawancara')?->nilai }}/5</td></tr>
                  @endif
                  @if ($bobotMansoskul['presentasi'] > 0)
                  <tr><td class="text-muted pl-0">Presentasi ({{ $bobotMansoskul['presentasi'] }}%)</td><td class="text-right pr-0">{{ $nilaiManual->get('mansoskul_presentasi')?->nilai }}/5</td></tr>
                  @endif
                </table>
              </div>
            </div>
          </div>
        </div>
        @endif

      </div>
    </div>

  </div>
</div>
@endsection
