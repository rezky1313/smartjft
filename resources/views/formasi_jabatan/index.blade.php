@extends('layouts.users.master')
@section('title', 'Pusbin JFT')

@section('isi')
<div class="container-fluid">

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="preview-card mb-4">
    <div class="preview-header d-flex justify-content-between align-items-center flex-wrap">
      <div>
        <span class="preview-header-title">Data Formasi</span>
        <span class="preview-header-subtitle d-block">Kelola kuota formasi JFT per unit kerja</span>
      </div>
      @can('create formasi')
      <div>
        <a href="{{ route('user.formasi.create') }}" class="btn btn-primary btn-sm mr-1">+ Tambah Formasi</a>
        <a href="{{ route('user.formasi.import-pivot.form') }}" class="btn btn-primary btn-sm mr-1">+ Import Excel</a>
        <a href="{{ route('user.formasi.template') }}" class="btn btn-outline-primary btn-sm">
          <i class="fas fa-file-excel mr-1"></i> Download Template
        </a>
      </div>
      @endcan
    </div>

    <div class="preview-body">
      {{-- Filter --}}
      <form method="get" class="d-flex flex-wrap align-items-stretch">
        <select name="province_id" id="provFilter" class="form-control mr-2 mb-2" style="width:auto;">
          <option value="">Semua Provinsi</option>
          @foreach($provinces ?? [] as $p)
            <option value="{{ $p->id }}" @selected(($filter['province_id'] ?? '') == $p->id)>{{ $p->name }}</option>
          @endforeach
        </select>

        <select name="regency_id" id="regFilter" class="form-control mr-2 mb-2" style="width:auto;">
          <option value="">Semua Kab/Kota</option>
          @foreach($regencies ?? [] as $r)
            <option value="{{ $r->id }}" @selected(($filter['regency_id'] ?? '') == $r->id)>{{ $r->type }} {{ $r->name }}</option>
          @endforeach
        </select>

        <select name="unit_kerja_id" id="unitFilter" class="form-control mr-2 mb-2" style="width:auto;">
          <option value="">Semua Unit Kerja</option>
          @foreach($units as $u)
            <option value="{{ $u->id }}" data-regency="{{ $u->regency_id ?? '' }}" @selected(($filter['unit_kerja_id']??'')==$u->id)>
              {{ $u->nama_unit_kerja }}
            </option>
          @endforeach
        </select>

        <select name="tahun" class="form-control mr-2 mb-2" style="width:auto;">
          <option value="">Semua Tahun</option>
          @foreach($tahuns as $t)
            <option value="{{ $t }}" @selected(($filter['tahun']??'')==$t)>{{ $t }}</option>
          @endforeach
        </select>

        <button class="btn btn-outline-primary mr-2 mb-2">Terapkan</button>
        <a href="{{ route('user.formasi.index') }}" class="btn btn-outline-secondary mb-2">Reset</a>
      </form>

      @can('edit formasi')
      <div>
        <button id="btn-edit-group" type="button" class="btn btn-warning btn-sm">
          <i class="fas fa-edit mr-1"></i> Edit Grup: Unit & Tahun Terpilih
        </button>
      </div>
      @endcan
    </div>
  </div>


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
  <div class="preview-card mb-4">
    <div class="card-header-custom">
      <span class="card-header-title-custom">{{ $unitName }}</span>
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
