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

  <select name="unit_kerja_id" id="unitFilter" class="form-select">
    <option value="">Semua Unit Kerja</option>
    @foreach($units as $u)
      <option value="{{ $u->no_rs }}" data-regency="{{ $u->regency_id ?? '' }}" @selected(($filter['unit_kerja_id']??'')==$u->no_rs)>
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


@can('edit formasi')
<div class="mb-3">
  <button id="btn-edit-group" type="button" class="btn btn-warning">
    Edit Grup: Unit & Tahun Terpilih
  </button>
</div>
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

  /* Tombol actions di sebelah kanan table */
  .formasi-actions-col {
    min-width: 60px;
  }
  .formasi-actions-col .btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
</style>
@endpush

@push('scripts')
<script>
  //Edit Button
  document.getElementById('btn-edit-group')?.addEventListener('click', function(){
    const unit  = document.querySelector('select[name="unit_kerja_id"]').value;
    const tahun = document.querySelector('select[name="tahun"]').value;
    if (!unit || !tahun) {
      alert('Pilih Unit Kerja dan Tahun terlebih dahulu untuk mengedit formasi.');
      return;
    }
    const url = @json(route('user.formasi.edit-group')) + '?unit=' + encodeURIComponent(unit) + '&tahun=' + encodeURIComponent(tahun);
    window.location.href = url;
  });

  //Delete Button per Unit Kerja
  function confirmDeleteUnitFormasi(btn) {
    const unitId = btn.dataset.unitId;
    const unitName = btn.dataset.unitName;
    const tahun = btn.dataset.tahun;

    let message = tahun
      ? `Apakah Anda yakin ingin menghapus SEMUA data formasi untuk:\n\nUnit Kerja: ${unitName}\nTahun: ${tahun}\n\nData yang dihapus tidak dapat dikembalikan!`
      : `Apakah Anda yakin ingin menghapus SEMUA data formasi untuk:\n\nUnit Kerja: ${unitName}\nSemua Tahun\n\nData yang dihapus tidak dapat dikembalikan!`;

    if (!confirm(message)) {
      return;
    }

    // Kirim request delete
    let url = @json(route('user.formasi.delete-group')) + '?unit=' + encodeURIComponent(unitId);
    if (tahun) {
      url += '&tahun=' + encodeURIComponent(tahun);
    }

    // Buat form untuk submit DELETE
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;

    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';

    form.appendChild(csrfInput);
    form.appendChild(methodInput);
    document.body.appendChild(form);
    form.submit();
  }
//----------------------------------

  (function(){
    const baseUrl = @json(route('user.wilayah.regencies', ['province' => '__PID__']));
    const $prov = document.getElementById('provFilter');
    const $reg  = document.getElementById('regFilter');
    const $unit = document.getElementById('unitFilter');

    // Simpan semua option unit kerja untuk filtering
    const allUnitOptions = Array.from($unit.querySelectorAll('option[data-regency]'));

    async function loadRegencies(pid, selected=''){
      if(!pid){
        $reg.innerHTML = `<option value="">Semua Kab/Kota</option>`;
        filterUnitsByRegency('');
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

    function filterUnitsByRegency(regencyId){
      // Bersihkan option yang ada
      $unit.innerHTML = '<option value="">Semua Unit Kerja</option>';

      // Filter dan tambahkan option sesuai regency
      const filtered = regencyId
        ? allUnitOptions.filter(opt => opt.dataset.regency === String(regencyId))
        : allUnitOptions;

      filtered.forEach(opt => {
        $unit.appendChild(opt.cloneNode(true));
      });

      // Restore selected value jika masih ada di filtered options
      const currentValue = @json($filter['unit_kerja_id'] ?? '');
      if(currentValue && $unit.querySelector(`option[value="${currentValue}"]`)){
        $unit.value = currentValue;
      }
    }

    // on change provinsi
    $prov?.addEventListener('change', e => {
      loadRegencies(e.target.value, '');
      filterUnitsByRegency(''); // Reset unit filter saat provinsi berubah
    });

    // on change kab/kota
    $reg?.addEventListener('change', e => {
      filterUnitsByRegency(e.target.value);
    });

    // init: kalau halaman dibuka dg province terpilih, muat regencynya
    const initialProv = @json($filter['province_id'] ?? '');
    const initialReg  = @json($filter['regency_id'] ?? '');
    if(initialProv){ loadRegencies(initialProv, initialReg); }
    // Filter unit kerja berdasarkan regency yang terpilih saat load
    if(initialReg){ filterUnitsByRegency(initialReg); }
  })();
</script>
@endpush





@forelse($table as $unitName => $rows)
  @php
    $meta = $rows['_meta'] ?? [];
    $unitId = $meta['unit_kerja_id'] ?? null;
    // Gunakan tahun yang difilter, atau tahun pertama dari metadata, atau null
    $editTahun = $filter['tahun'] ?? ($meta['tahuns'][0] ?? null);
  @endphp
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0">{{ $unitName }}</h5>
    </div>
    <div class="card-body p-0">
      <div class="row g-0">
        {{-- Tabel --}}
        <div class="col-md">
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
    @foreach($rows as $key => $row)
      @if($key === '_meta') @continue @endif
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
          <td @class(['border-start-thick'=>$loop->first, 'text-danger fw-bold'=>$s[$c] < 0, 'text-warning fw-bold'=>$s[$c] == 0])>{{ $s[$c] }}</td>
        @endforeach
        <td @class(['text-danger fw-bold'=>$sTotal < 0, 'text-warning fw-bold'=>$sTotal == 0])><b>{{ $sTotal }}</b></td>
      </tr>
    @endforeach
  </tbody>
</table>

          </div>
        </div>

        {{-- Tombol Actions di sebelah kanan --}}
        <div class="col-auto formasi-actions-col d-flex flex-column justify-content-start gap-2 p-3 border-start bg-light">
          @can('edit formasi')
          @if($unitId && $editTahun)
          <a href="{{ route('user.formasi.edit-group', ['unit' => $unitId, 'tahun' => $editTahun]) }}"
             class="btn btn-warning btn-sm" title="Edit Formasi">
             <i class="fas fa-edit"></i>
          </a>
          @endif
          @endcan

          @can('delete formasi')
          @if($unitId)
          <button type="button"
                  class="btn btn-danger btn-sm"
                  data-unit-id="{{ $unitId }}"
                  data-unit-name="{{ $unitName }}"
                  data-tahun="{{ $editTahun }}"
                  onclick="confirmDeleteUnitFormasi(this)"
                  title="Hapus Formasi">
            <i class="fas fa-trash"></i>
          </button>
          @endif
          @endcan
        </div>
      </div>
    </div>
  </div>
@empty
  <div class="alert alert-info mb-4">Belum ada data.</div>
@endforelse
</div>
@endsection
