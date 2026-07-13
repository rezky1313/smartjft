@extends('layouts.users.master')

@section('title', 'Detail Soal #' . $soal->id . ' — Pusbin JFT')

@section('isi')
<div class="row justify-content-center">
  <div class="col-lg-10">

    {{-- Header --}}
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="fas fa-file-alt mr-2"></i>Detail Soal</h3>
        <a href="{{ route('bank-soal.index') }}" class="btn btn-default btn-sm">
          <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
      </div>
      <div class="card-body">
        @if (session('success'))
        <div class="alert alert-success py-2 mb-3">{{ session('success') }}</div>
        @endif
        @if (session('error'))
        <div class="alert alert-danger py-2 mb-3">{{ session('error') }}</div>
        @endif

        <div class="row">
          <div class="col-md-2">
            <p class="mb-1 text-muted small font-weight-bold">ID SOAL</p>
            <p class="mb-0"><strong>#{{ $soal->id }}</strong></p>
          </div>
          <div class="col-md-3">
            <p class="mb-1 text-muted small font-weight-bold">{{ $soal->jenis === 'teknis' ? 'KATEGORI' : 'MATRA' }}</p>
            <p class="mb-0">
              @if ($soal->jenis === 'teknis')
                {{ $soal->kategori?->nama ?? '-' }}
              @else
                @if ($soal->matra)
                  {{ $soal->label_matra }}
                @else
                  <span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Belum Diisi</span>
                @endif
              @endif
            </p>
          </div>
          <div class="col-md-2">
            <p class="mb-1 text-muted small font-weight-bold">JENIS</p>
            <p class="mb-0">
              <span class="badge badge-{{ $soal->jenis === 'mansoskul' ? 'secondary' : 'info' }}">
                {{ $soal->label_jenis }}
              </span>
            </p>
          </div>
          <div class="col-md-3">
            <p class="mb-1 text-muted small font-weight-bold">TAKSONOMI</p>
            <p class="mb-0">
              <span class="badge badge-secondary">{{ $soal->label_taksonomi }}</span>
            </p>
          </div>
          <div class="col-md-2">
            <p class="mb-1 text-muted small font-weight-bold">STATUS</p>
            <p class="mb-0">
              @php $badgeStatus = ['aktif'=>'success','draft'=>'secondary','nonaktif'=>'danger']; @endphp
              <span class="badge badge-{{ $badgeStatus[$soal->status] ?? 'secondary' }}">{{ $soal->label_status }}</span>
            </p>
          </div>
        </div>

        <hr class="my-3">

        <div class="row">
          <div class="col-md-6">
            <p class="mb-1 text-muted small font-weight-bold">DIBUAT OLEH</p>
            <p class="mb-0"><i class="fas fa-user mr-1 text-muted"></i>{{ $soal->pembuat?->name ?? '-' }}</p>
            <p class="mb-0 text-muted small">{{ $soal->created_at->format('d F Y, H:i') }} WIB</p>
          </div>
          @if ($soal->penyetuju)
          <div class="col-md-6">
            <p class="mb-1 text-muted small font-weight-bold">DISETUJUI OLEH</p>
            <p class="mb-0"><i class="fas fa-user-check mr-1 text-success"></i>{{ $soal->penyetuju->name }}</p>
            <p class="mb-0 text-muted small">{{ $soal->tanggal_disetujui?->format('d F Y, H:i') }} WIB</p>
          </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Pertanyaan --}}
    <div class="card">
      <div class="card-header bg-light">
        <h3 class="card-title mb-0"><i class="fas fa-question-circle mr-2 text-primary"></i>Pertanyaan</h3>
      </div>
      <div class="card-body">
        <p class="mb-0" style="font-size:1.05rem; line-height:1.7;">{{ $soal->pertanyaan }}</p>
      </div>
    </div>

    {{-- Pilihan Jawaban --}}
    <div class="card">
      <div class="card-header bg-light">
        <h3 class="card-title mb-0"><i class="fas fa-list-ul mr-2"></i>Pilihan Jawaban</h3>
      </div>
      <div class="card-body">
        @if ($soal->jenis === 'teknis')
          @foreach ($soal->pilihan as $p)
          <div class="d-flex align-items-start mb-2 p-2 rounded"
               style="{{ $p->is_benar ? 'background:#d4edda;border:1px solid #c3e6cb;' : 'background:#f8f9fa;border:1px solid #dee2e6;' }}">
            <span class="badge mr-3 mt-1 {{ $p->is_benar ? 'badge-success' : 'badge-secondary' }}"
                  style="font-size:0.9rem;min-width:28px;padding:5px 8px;">
              {{ $p->kode_pilihan }}
            </span>
            <span style="font-size:0.95rem;">{{ $p->teks_pilihan }}</span>
            @if ($p->is_benar)
            <span class="ml-auto text-success small font-weight-bold">
              <i class="fas fa-check-circle mr-1"></i>Jawaban Benar
            </span>
            @endif
          </div>
          @endforeach
        @else
          <p class="text-muted small mb-3"><i class="fas fa-info-circle mr-1"></i>Soal Mansoskul tidak punya jawaban benar/salah — tiap pilihan punya nilai skala 1-5 sendiri.</p>
          @foreach ($soal->pilihan as $p)
          <div class="d-flex align-items-center mb-2 p-2 rounded" style="background:#f8f9fa;border:1px solid #dee2e6;">
            <span class="badge badge-secondary mr-3" style="font-size:0.9rem;min-width:28px;padding:5px 8px;">
              {{ $p->kode_pilihan }}
            </span>
            <span style="font-size:0.95rem;" class="flex-fill">{{ $p->teks_pilihan }}</span>
            <span class="badge badge-info ml-auto">Nilai {{ $p->nilai_skala ?? '-' }}</span>
          </div>
          @endforeach
        @endif
      </div>
    </div>

    {{-- Pembahasan --}}
    @if ($soal->pembahasan)
    <div class="card">
      <div class="card-header bg-light">
        <h3 class="card-title mb-0"><i class="fas fa-lightbulb mr-2 text-warning"></i>Pembahasan</h3>
      </div>
      <div class="card-body">
        <p class="mb-0" style="line-height:1.7;">{{ $soal->pembahasan }}</p>
      </div>
    </div>
    @endif

    {{-- Tombol Aksi --}}
    @hasanyrole('super_admin|admin')
    <div class="card">
      <div class="card-body d-flex flex-wrap gap-2">
        <a href="{{ route('bank-soal.edit', $soal->id) }}" class="btn btn-warning mr-2">
          <i class="fas fa-pencil-alt mr-1"></i> Edit
        </a>
        @if ($soal->status === 'draft')
        <form action="{{ route('bank-soal.approve', $soal->id) }}" method="POST" class="d-inline mr-2"
              onsubmit="return confirm('Aktifkan soal ini?')">
          @csrf
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check-circle mr-1"></i> Aktifkan
          </button>
        </form>
        @elseif ($soal->status === 'aktif')
        <form action="{{ route('bank-soal.nonaktifkan', $soal->id) }}" method="POST" class="d-inline mr-2"
              onsubmit="return confirm('Nonaktifkan soal ini?')">
          @csrf
          <button type="submit" class="btn btn-secondary">
            <i class="fas fa-ban mr-1"></i> Nonaktifkan
          </button>
        </form>
        @endif
        <form action="{{ route('bank-soal.destroy', $soal->id) }}" method="POST" class="d-inline"
              onsubmit="return confirm('Hapus soal ini secara permanen?')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-trash mr-1"></i> Hapus
          </button>
        </form>
      </div>
    </div>
    @endhasanyrole

  </div>
</div>
@endsection
