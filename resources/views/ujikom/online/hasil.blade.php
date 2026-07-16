@extends('layouts.users.master')

@section('title', 'Hasil Ujian — Pusbin JFT')

@section('isi')
<div class="row justify-content-center">
  <div class="col-lg-6 col-md-8">

    <div class="card card-outline card-{{ $sesi->status_lulus === 'lulus' ? 'success' : 'danger' }}">
      <div class="card-body text-center py-4">

        {{-- Icon --}}
        <div class="mb-3">
          @if ($sesi->status_lulus === 'lulus')
          <i class="fas fa-check-circle text-success" style="font-size:4rem;"></i>
          @else
          <i class="fas fa-times-circle text-danger" style="font-size:4rem;"></i>
          @endif
        </div>

        <h4 class="mb-1">Ujian Selesai!</h4>
        <p class="text-muted mb-3">
          {{ $sesi->peserta?->pegawai?->nama_lengkap ?? '-' }}<br>
          <small>{{ $sesi->jadwal->judul }}</small>
        </p>

        {{-- Card Nilai --}}
        <div class="mx-auto mb-4" style="max-width:280px;">
          <div class="card border-{{ $sesi->status_lulus === 'lulus' ? 'success' : 'danger' }} mb-0">
            <div class="card-body py-3">
              <small class="text-muted d-block mb-1">NILAI AKHIR</small>
              <h1 class="mb-1 text-{{ $sesi->status_lulus === 'lulus' ? 'success' : 'danger' }}" style="font-size:3rem; font-weight:800;">
                {{ number_format($sesi->nilai_akhir, 0) }}
              </h1>
              <small class="text-muted">Passing Grade: {{ $sesi->paketUjian->passing_grade }}%</small>
              <div class="mt-2">
                @if ($sesi->status_lulus === 'lulus')
                <span class="badge badge-success px-3 py-2" style="font-size:0.9rem;">LULUS</span>
                @else
                <span class="badge badge-danger px-3 py-2" style="font-size:0.9rem;">TIDAK LULUS</span>
                @endif
              </div>
            </div>
          </div>
        </div>

        {{-- Statistik --}}
        <div class="row text-center mb-3">
          <div class="col-4">
            <div class="p-2 bg-light rounded">
              <i class="fas fa-check text-success d-block mb-1"></i>
              <strong class="d-block">{{ $sesi->jumlah_benar ?? 0 }}</strong>
              <small class="text-muted">Benar</small>
            </div>
          </div>
          <div class="col-4">
            <div class="p-2 bg-light rounded">
              <i class="fas fa-times text-danger d-block mb-1"></i>
              <strong class="d-block">{{ $sesi->jumlah_salah ?? 0 }}</strong>
              <small class="text-muted">Salah</small>
            </div>
          </div>
          <div class="col-4">
            <div class="p-2 bg-light rounded">
              <i class="fas fa-minus text-secondary d-block mb-1"></i>
              <strong class="d-block">{{ $sesi->jumlah_kosong ?? 0 }}</strong>
              <small class="text-muted">Kosong</small>
            </div>
          </div>
        </div>

        {{-- Durasi --}}
        @if ($durasi)
        <p class="text-muted small mb-3">
          <i class="far fa-clock mr-1"></i>
          Durasi pengerjaan:
          @if ($durasi->h > 0) {{ $durasi->h }} jam @endif
          {{ $durasi->i }} menit {{ $durasi->s }} detik
        </p>
        @endif

        <p class="text-muted small mb-3">
          <i class="far fa-calendar mr-1"></i>
          Selesai: {{ $sesi->waktu_selesai?->format('d M Y H:i') }} WIB
          @if ($sesi->status_sesi === 'timeout')
          <br><span class="badge badge-warning">Timeout — waktu habis</span>
          @elseif ($sesi->status_sesi === 'disubmit_paksa')
          <br><span class="badge badge-dark">Disubmit paksa — terindikasi pelanggaran (3x)</span>
          @endif
        </p>

        <hr>
        <a href="{{ route('ujikom-online.index') }}" class="btn btn-outline-primary">
          <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Ujian
        </a>

      </div>
    </div>

  </div>
</div>
@endsection
