@extends('layouts.users.master')
@section('title', 'Tambah Formasi (Multi)')

@section('isi')
<div class="container-fluid">
  <h4 class="mb-4">Tambah Banyak Formasi Sekaligus</h4>

  {{-- Notifikasi error --}}
  @if ($errors->any())
    <div class="alert alert-danger">
      <div class="fw-bold mb-1">Periksa kembali input Anda:</div>
      <ul class="mb-0">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif


  <form method="post" action="{{ route('user.formasi.store') }}">
  @csrf

   {{-- saat edit, kirimkan hidden --}}
  {{-- @if($mode==='edit')
    <input type="hidden" name="unit_kerja_id" value="{{ $unit->no_rs }}">
    <input type="hidden" name="tahun_formasi" value="{{ $tahun }}">
  @endif --}}

    {{-- Header: Unit Kerja & Tahun --}}
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="card-title mb-0">Informasi Umum</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-lg-6">
            <label class="form-label">Unit Kerja</label>
            <select name="unit_kerja_id" id="unit_kerja_id" class="form-select select2" required>
              <option value="">-- Pilih Unit Kerja --</option>
              @foreach ($unitkerja as $unit)
                <option value="{{ $unit->no_rs }}" @selected(old('unit_kerja_id')==$unit->no_rs)>
                  {{ $unit->nama_rumahsakit }}
                </option>
              @endforeach
            </select>
            @error('unit_kerja_id') <small class="text-danger">{{ $message }}</small> @enderror
          </div>
          <div class="col-lg-6">
            <label class="form-label">Tahun Formasi</label>
            <input type="text" name="tahun_formasi" id="tahun_formasi" class="form-control"
                   value="{{ old('tahun_formasi') }}" placeholder="mis. 2025" required>
            @error('tahun_formasi') <small class="text-danger">{{ $message }}</small> @enderror
          </div>
        </div>
      </div>
    </div>

    {{-- Tabel Items --}}
    <div class="card">
      <div class="card-header d-flex align-items-center">
        <h5 class="card-title mb-0">Daftar Formasi</h5>
        <div class="ms-auto">
          <button type="button" class="btn btn-outline-secondary" id="btn-add-row">+ Tambah Baris</button>
        </div>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered align-middle mb-0" id="tbl-rows">
            <thead class="table-light">
              <tr>
                <th style="width:48px">#</th>
                <th style="min-width:260px">Nama Formasi</th>
                <th style="min-width:220px">Jenjang</th>
                <th style="width:140px">Kuota</th>
                <th style="width:64px"></th>
              </tr>
            </thead>
            <tbody>
              {{-- Satu baris awal --}}
              <tr>
                <td class="row-no"></td>
                <td>
                  <select name="items[0][nama_formasi]" class="form-select sel-formasi" required>
                    <option value="">-- Pilih Nama Formasi --</option>
                    @foreach(($daftarFormasi ?? []) as $f)
                      <option value="{{ $f }}" @selected(old('items.0.nama_formasi')===$f)>{{ $f }}</option>
                    @endforeach
                  </select>
                </td>
                <td>
                  <select name="items[0][jenjang_id]" class="form-select sel-jenjang" required>
                    <option value="">-- Pilih Jenjang Jabatan --</option>
                    @foreach ($jenjang->groupBy('kategori') as $kategori => $items)
                      <optgroup label="{{ $kategori }}">
                        @foreach ($items as $item)
                          <option value="{{ $item->id }}" @selected(old('items.0.jenjang_id')==$item->id)>
                            {{ $item->nama_jenjang }}
                          </option>
                        @endforeach
                      </optgroup>
                    @endforeach
                  </select>
                </td>
                <td>
                  <input type="number" min="0" name="items[0][kuota]" class="form-control" value="{{ old('items.0.kuota', 0) }}" required>
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-danger btn-del" title="Hapus baris">&times;</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer d-flex gap-2">
        <a href="{{ route('user.formasi.index') }}" class="btn btn-secondary">Kembali</a>
        <button class="btn btn-primary ms-auto">Simpan</button>
      </div>
    </div>
  </form>
</div>
@endsection

@push('styles')
  {{-- Select2 (opsional, kalau sudah include di layout boleh dihapus) --}}
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
  <style>
    /* Biar nomor baris rapi */
    #tbl-rows .row-no{ width:48px; text-align:center; }
  </style>
@endpush

@push('scripts')
  {{-- Select2 (opsional, kalau sudah include di layout boleh dihapus) --}}
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
  (function(){
    const tbl   = document.querySelector('#tbl-rows tbody');
    const btnAdd= document.querySelector('#btn-add-row');

    function initSelect2(scope){
      // Inisialisasi select2 untuk elemen baru (jika dipakai)
      const selects = (scope ?? document).querySelectorAll('.select2, .sel-formasi, .sel-jenjang');
      window.jQuery && selects.forEach(el => window.jQuery(el).select2({ width:'100%' }));
    }

    function renumber(){
      tbl.querySelectorAll('tr').forEach((tr,idx)=>{
        tr.querySelector('.row-no').textContent = idx+1;
        tr.querySelectorAll('input,select').forEach(el=>{
          // perbaikan regex: ganti index di "items[n]"
          el.name = el.name.replace(/items\[\d+\]/, 'items['+idx+']');
        });
      });
    }

    btnAdd?.addEventListener('click', ()=>{
      const idx = tbl.querySelectorAll('tr').length;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="row-no"></td>
        <td>
          <select name="items[${idx}][nama_formasi]" class="form-select sel-formasi" required>
            <option value="">-- Pilih Nama Formasi --</option>
            @foreach(($daftarFormasi ?? []) as $f)
              <option value="{{ $f }}">{{ $f }}</option>
            @endforeach
          </select>
        </td>
        <td>
          <select name="items[${idx}][jenjang_id]" class="form-select sel-jenjang" required>
            <option value="">-- Pilih Jenjang Jabatan --</option>
            @foreach ($jenjang->groupBy('kategori') as $kategori => $items)
              <optgroup label="{{ $kategori }}">
                @foreach ($items as $item)
                  <option value="{{ $item->id }}">{{ $item->nama_jenjang }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
        </td>
        <td>
          <input type="number" min="0" name="items[${idx}][kuota]" class="form-control" value="0" required>
        </td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger btn-del" title="Hapus baris">&times;</button>
        </td>
      `;
      tbl.appendChild(tr);
      renumber();
      initSelect2(tr);
    });

    tbl?.addEventListener('click', (e)=>{
      if(e.target.closest('.btn-del')){
        const all = tbl.querySelectorAll('tr');
        if(all.length === 1){
          // kalau tinggal 1 baris, kosongkan saja
          const tr = all[0];
          tr.querySelectorAll('input').forEach(i=> i.value = (i.type==='number' ? 0 : ''));
          tr.querySelectorAll('select').forEach(s=> s.selectedIndex = 0);
        } else {
          e.target.closest('tr').remove();
          renumber();
        }
      }
    });

    // init awal
    initSelect2();
    renumber();
  })();
  </script>
@endpush
