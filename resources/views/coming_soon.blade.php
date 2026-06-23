@extends('layouts.users.master')

@section('title', ($judul ?? 'Segera Hadir') . ' — Pusbin JFT')

@section('isi')
<div class="row justify-content-center" style="margin-top: 60px;">
  <div class="col-md-8 col-lg-6 text-center">
    <div class="card shadow-sm border-0">
      <div class="card-body py-5 px-4">
        <div style="font-size: 5rem; color: #dee2e6; margin-bottom: 1rem;">
          <i class="fas fa-tools"></i>
        </div>
        <h2 class="font-weight-bold text-dark mb-2">{{ $judul ?? 'Segera Hadir' }}</h2>
        <p class="text-muted mb-4" style="font-size: 1.05rem;">
          Halaman ini sedang dalam pengembangan.<br>Segera hadir!
        </p>
        <span class="badge badge-warning px-3 py-2" style="font-size: 0.9rem;">
          <i class="fas fa-clock mr-1"></i> Coming Soon
        </span>
        <div class="mt-4">
          <a href="{{ route('user.peta') }}" class="btn btn-primary">
            <i class="fas fa-home mr-1"></i> Kembali ke Dashboard
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
