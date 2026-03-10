@extends('layouts.users.master')
@section('title', 'Pusbin JFT')

@section('isi')
<div class="container-fluid">
  <h4 class="mb-3">Data Formasi</h4>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  {{-- Toolbar --}}
  <div class="d-flex flex-wrap gap-2 mb-3">
    @can('create formasi')
    <a href="{{ route('user.formasi.create') }}" class="btn btn-primary">+ Tambah Formasi</a>
    <a href="{{ route('user.formasi.import-pivot.form') }}" class="btn btn-primary">+ Import Excel</a>
    @endcan

    {{-- <a href="{{ route('user.formasi.trash') }}" class="btn btn-outline-secondary">
      Sampah @if(!empty($trashed)) <span class="badge bg-secondary">{{ $trashed }}</span>@endif
    </a> --}}

    {{-- Filter --}}
{{-- Filter --}}
<form method="get" class="ms-auto d-flex flex-wrap gap-2 align-items-stretch">
  <select name="province_id" id="provFilter" class="form-select">
    <option value="">Semua Provinsi</option>
    @foreach($provinces ?? [] as $p)
      <option value="{{ $p->id }}" @selected(($filter['province_id'] ?? '') == $p->id)>{{ $p->name }}</option>
    @endforeach
  </select>

  <select name="regency_id" id="regFilter" class="form-select">
    <option value="">Semua Kab/Kota</option>
    @foreach($regencies ?? [] as $r)
      <option value="{{ $r->id }}" @selected(($filter['regency_id'] ?? '') == $r->id)>{{ $r->type }} {{ $r->name }}</option>
    @endforeach
  </select>

  <select name="unit_kerja_id" class="form-select">
    <option value="">Semua Unit Kerja</option>
    @foreach($units as $u)
      <option value="{{ $u->no_rs }}" @selected(($filter['unit_kerja_id']??'')==$u->no_rs)>
        {{ $u->nama_rumahsakit }}
      </option>
    @endforeach
  </select>

  <select name="tahun" class="form-select">
    <option value="">Semua Tahun</option>
    @foreach($tahuns as $t)
      <option value="{{ $t }}" @selected(($filter['tahun']??'')==$t)>{{ $t }}</option>
    @endforeach
  </select>

  <button class="btn btn-outline-primary">Terapkan</button>
  <a href="{{ route('user.formasi.index') }}" class="btn btn-outline-secondary">Reset</a>
</form>


  {{-- Tombol Edit Grup (aktif jika filter lengkap) --}}
  {{-- @if(!empty($filter['unit_kerja_id']) && !empty($filter['tahun']))
    <div class="mb-3">
      <a class="btn btn-warning"
         href="{{ route('user.formasi.edit', ['unit_kerja_id'=>$filter['unit_kerja_id'], 'tahun_formasi'=>$filter['tahun']]) }}">
        Edit Grup: Unit & Tahun Terpilih
      </a>
    </div>
  @endif --}}

    {{-- <div class="mb-3">
  <button id="btn-edit-group" type="button" class="btn btn-warning">
    Edit Grup: Unit & Tahun Terpilih
  </button>
</div> --}}

@can('edit formasi')
@if(!empty($filter['unit_kerja_id']) && !empty($filter['tahun']))
  <div class="mb-3">
    <a class="btn btn-warning"
       href="{{ route('user.formasi.edit-group', [
          'unit'  => $filter['unit_kerja_id'],
          'tahun' => $filter['tahun']
       ]) }}">
      Edit Grup: Unit & Tahun Terpilih
    </a>
  </div>
@endif
@endcan


 {{-- Tabel Pivot per Unit Kerja --}}
@push('styles')
<style>
  .pivot-table.table.table-bordered thead th,
  .pivot-table.table.table-bordered tbody td {
    border: 2px solid #cfd1d1; /* default bootstrap */
  }
  .pivot-table .border-end-thick   { border-right: 2px solid #000 !important; }
  .pivot-table .border-start-thick { border-left:  2px solid #000 !important; }

   form .form-select { min-width: 220px; }
</style>
@endpush

@push('scripts')
<script>
  //Edit Button
  document.getElementById('btn-edit-group')?.addEventListener('click', function(){
  const unit  = document.querySelector('select[name="unit_kerja_id"]').value;
  const tahun = document.querySelector('select[name="tahun"]').value;
  if (!unit || !tahun) {
    alert('Pilih Unit Kerja dan Tahun terlebih dahulu.');
    return;
  }
  const url = @json(route('user.formasi.edit-group', ['unit_kerja_id'=>'__UNIT__','tahun_formasi'=>'__TAHUN__']));
  window.location.href = url.replace('__UNIT__', unit).replace('__TAHUN__', tahun);
});
//----------------------------------

  (function(){
    const baseUrl = @json(route('user.wilayah.regencies', ['province' => '__PID__']));
    const $prov = document.getElementById('provFilter');
    const $reg  = document.getElementById('regFilter');

    async function loadRegencies(pid, selected=''){
      if(!pid){
        $reg.innerHTML = `<option value="">Semua Kab/Kota</option>`;
        return;
      }
      const url = baseUrl.replace('__PID__', pid);
      const res = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
      const data = await res.json();
      let html = `<option value="">Semua Kab/Kota</option>`;
      (data||[]).forEach(r=>{
        const id = r.id ?? r.value, name = r.name ?? r.text ?? '';
        html += `<option value="${id}" ${String(selected)===String(id)?'selected':''}>${(r.type?r.type+' ':'')+name}</option>`;
      });
      $reg.innerHTML = html;
    }

    // on change
    $prov?.addEventListener('change', e => loadRegencies(e.target.value, ''));

    // init: kalau halaman dibuka dg province terpilih, muat regencynya
    const initialProv = @json($filter['province_id'] ?? '');
    const initialReg  = @json($filter['regency_id'] ?? '');
    if(initialProv){ loadRegencies(initialProv, initialReg); }
  })();
</script>
@endpush





@forelse($table as $unitName => $rows)
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0">{{ $unitName }}</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
      <table class="table table-bordered table-sm mb-0 align-middle text-center pivot-table">
  <thead>
    <tr>
      <th rowspan="3" style="width:50px">No</th>
      <th rowspan="3" class="text-start">Nama Jabatan</th>

      {{-- KUOTA --}}
      <th colspan="{{ count($cols)+1 }}" class="border-end-thick">Kuota</th>
      {{-- TERISI --}}
      <th colspan="{{ count($cols)+1 }}" class="border-end-thick border-start-thick">Terisi (Eksisting)</th>
      {{-- SISA --}}
      <th colspan="{{ count($cols)+1 }}" class="border-start-thick">Sisa</th>
    </tr>
    <tr>
      {{-- Kuota --}}
      @foreach($cols as $c)
        <th @class(['border-start-thick'=>$loop->first])>{{ $c }}</th>
      @endforeach
      <th class="border-end-thick">TOTAL</th>

      {{-- Terisi --}}
      @foreach($cols as $c)
        <th @class(['border-start-thick'=>$loop->first])>{{ $c }}</th>
      @endforeach
      <th class="border-end-thick">TOTAL</th>

      {{-- Sisa --}}
      @foreach($cols as $c)
        <th @class(['border-start-thick'=>$loop->first])>{{ $c }}</th>
      @endforeach
      <th>TOTAL</th>
    </tr>
    <tr></tr>
  </thead>
  <tbody>
    @php $i=1; @endphp
    @foreach($rows as $row)
      @php
        $k = $row['kuota'];  $t = $row['terisi'];  $s = $row['sisa'];
        $kTotal = array_sum($k); $tTotal = array_sum($t); $sTotal = array_sum($s);
      @endphp
      <tr>
        <td>{{ $i++ }}</td>
        <td class="text-start">{{ $row['jabatan'] }}</td>

        {{-- KUOTA --}}
        @foreach($cols as $c)
          <td @class(['border-start-thick'=>$loop->first])>{{ $k[$c] }}</td>
        @endforeach
        <td class="border-end-thick"><b>{{ $kTotal }}</b></td>

        {{-- TERISI --}}
        @foreach($cols as $c)
          <td @class(['border-start-thick'=>$loop->first])>{{ $t[$c] }}</td>
        @endforeach
        <td class="border-end-thick"><b>{{ $tTotal }}</b></td>

        {{-- SISA --}}
        @foreach($cols as $c)
          <td @class(['border-start-thick'=>$loop->first])>{{ $s[$c] }}</td>
        @endforeach
        <td><b>{{ $sTotal }}</b></td>
      </tr>
    @endforeach
  </tbody>
</table>



      </div>
    </div>
  </div>
@empty
  <div class="alert alert-info mb-4">Belum ada data.</div>
@endforelse
</div>
@endsection
