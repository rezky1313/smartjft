@extends('layouts.users.master')

@section('isi')
<h4>Detail Usulan #{{ $p->id }}</h4>

<div class="mb-3">
 <div><b>Pegawai:</b> {{ $p->sdm->nama_lengkap ?? '-' }}</div>
<div><b>Asal → Target:</b> {{ $p->jenjangAsal->nama_jenjang ?? '-' }} → {{ $p->jenjangTarget->nama_jenjang ?? '-' }}</div>
  <div><b>Status:</b> <span class="badge text-bg-secondary">{{ $p->status }}</span></div>
</div>

<h6>Berkas</h6>
<ul>
  {{-- @foreach($p->files as $f)
    <li>{{ strtoupper($f->kind) }} — <a href="{{ Storage::disk('public')->url($f->path) }}" target="_blank">lihat</a></li>
  @endforeach --}}

  {{-- @foreach($p->files as $f)
  @php
    $exists = Storage::disk('public')->exists($f->path);
    $url = Storage::disk('public')->url($f->path);  // -> /storage/promotion_files/xxx.pdf
    $label = strtoupper(str_replace('_',' ', $f->kind));
  @endphp

  @if($exists)
    <a class="btn btn-sm btn-outline-primary" href="{{ $url }}" target="_blank">Lihat {{ $label }}</a>
    <a class="btn btn-sm btn-outline-secondary" href="{{ $url }}" download>Unduh {{ $label }}</a>
  @else
    <span class="badge text-bg-danger">File {{ $label }} tidak ditemukan</span>
  @endif
@endforeach --}}

@foreach($p->files as $f)
  @php $label = strtoupper(str_replace('_',' ', $f->kind)); @endphp
  <a class="btn btn-sm btn-outline-primary"
     href="{{ route('user.promotions.files.inline', $f->id) }}" target="_blank">
     Lihat {{ $label }}
  </a>
  <a class="btn btn-sm btn-outline-secondary"
     href="{{ route('user.promotions.files.download', $f->id) }}">
     Unduh {{ $label }}
  </a>
@endforeach


</ul>

{{-- Semua aksi oleh admin --}}
<div class="mt-4 d-flex flex-wrap gap-2">
  @if($p->status === 'DRAFT' || $p->status === 'NEED_FIX')
    <form action="{{ route('user.promotions.submit',$p->id) }}" method="post">@csrf
      <button class="btn btn-primary">Ajukan (Submit)</button>
    </form>
  @endif

  @if($p->status === 'SUBMITTED')
    <form action="{{ route('user.promotions.verify',$p->id) }}" method="post">@csrf
      <button class="btn btn-success">Verifikasi</button>
    </form>
    <form action="{{ route('user.promotions.return',$p->id) }}" method="post" class="d-flex gap-2">
      @csrf
      <input type="text" name="note" class="form-control" placeholder="Alasan dikembalikan" required style="max-width:300px">
      <button class="btn btn-warning">Kembalikan</button>
    </form>
  @endif

  @if($p->status === 'VERIFIED')
    <form action="{{ route('user.promotions.approve',$p->id) }}" method="post" enctype="multipart/form-data" class="d-flex flex-wrap gap-2">
      @csrf
      <input type="text" name="sk_number" class="form-control" placeholder="Nomor Surat" required style="max-width:200px">
      <input type="date" name="tmt_sk" class="form-control" required style="max-width:180px">
      <input type="file" name="sk_file" class="form-control" accept="application/pdf" style="max-width:280px">
      <button class="btn btn-outline-primary">.</button>
    </form>

    <form action="{{ route('user.promotions.apply',$p->id) }}" method="post">@csrf
      <button class="btn btn-primary">Terapkan</button>
    </form>
  @endif
</div>


{{-- @if(!$isAdmin && in_array($p->status, ['DRAFT','NEED_FIX']))
  <form action="{{ route('promotions.submit',$p->id) }}" method="post" class="mt-3">
    @csrf
    <button class="btn btn-primary" type="submit">Ajukan</button>
  </form>
@endif

@if($isAdmin)
  <div class="mt-4 d-flex flex-wrap gap-2">
    @if($p->status === 'SUBMITTED')
      <form action="{{ route('promotions.verify',$p->id) }}" method="post">@csrf
        <button class="btn btn-success">Verifikasi</button>
      </form>

      <form action="{{ route('promotions.return',$p->id) }}" method="post" class="d-flex gap-2">
        @csrf
        <input type="text" name="note" class="form-control" placeholder="Alasan dikembalikan" required style="max-width:300px">
        <button class="btn btn-warning">Kembalikan</button>
      </form>
    @endif

    @if($p->status === 'VERIFIED')
      <form action="{{ route('promotions.approve',$p->id) }}" method="post" enctype="multipart/form-data" class="d-flex flex-wrap gap-2">
        @csrf
        <input type="text" name="sk_number" class="form-control" placeholder="Nomor SK" required style="max-width:200px">
        <input type="date" name="tmt_sk" class="form-control" required style="max-width:180px">
        <input type="file" name="sk_file" class="form-control" accept="application/pdf" style="max-width:280px">
        <button class="btn btn-outline-primary">Simpan SK & TMT</button>
      </form>

      <form action="{{ route('promotions.apply',$p->id) }}" method="post">
        @csrf
        <button class="btn btn-primary">Terapkan</button>
      </form>
    @endif
  </div>
@endif --}}

<hr>
<h6>Log Status</h6>
<ol>
  @foreach($p->logs as $log)
    <li>[{{ $log->created_at->format('d/m/Y H:i') }}] {{ $log->from_status ?? '—' }} → <b>{{ $log->to_status }}</b> — {{ $log->note ?? '' }}</li>
  @endforeach
</ol>

<a class="btn btn-light mt-3" href="{{ route('user.promotions.index') }}">Kembali</a>
@endsection
