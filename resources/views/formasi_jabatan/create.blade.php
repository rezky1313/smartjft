@extends('layouts.users.master')
@section('title', 'Tambah Formasi (Multi)')

@section('isi')
<div class="preview-card">
  <div class="preview-header">
    <span class="preview-header-title">Tambah Banyak Formasi Sekaligus</span>
    <span class="preview-header-subtitle d-block">Lengkapi data di bawah ini</span>
  </div>
  <div class="preview-body">

  {{-- Notifikasi error --}}
  @if ($errors->any())
    <div class="alert alert-danger">
      <div class="font-weight-bold mb-1">Periksa kembali input Anda:</div>
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
    <input type="hidden" name="unit_kerja_id" value="{{ $unit->id }}">
    <input type="hidden" name="tahun_formasi" value="{{ $tahun }}">
  @endif --}}

    {{-- Header: Unit Kerja & Tahun --}}
    <span class="subsection-label">Informasi Umum</span>
    <div class="form-row">
      <div class="form-group col-lg-6">
        <label class="font-weight-bold">Unit Kerja</label>
        <select name="unit_kerja_id" id="unit_kerja_id" class="form-control select2" required>
          <option value="">-- Pilih Unit Kerja --</option>
          @foreach ($unitkerja as $unit)
            <option value="{{ $unit->id }}" @selected(old('unit_kerja_id')==$unit->id)>
              {{ $unit->nama_unit_kerja }}
            </option>
          @endforeach
        </select>
        @error('unit_kerja_id') <small class="text-danger">{{ $message }}</small> @enderror
      </div>
      <div class="form-group col-lg-6">
        <label class="font-weight-bold">Tahun Formasi</label>
        <input type="text" name="tahun_formasi" id="tahun_formasi" class="form-control"
               value="{{ old('tahun_formasi') }}" placeholder="mis. 2025" required>
        @error('tahun_formasi') <small class="text-danger">{{ $message }}</small> @enderror
      </div>
    </div>

    <hr class="my-4">

    {{-- Tabel Items --}}
    <div class="d-flex align-items-center mb-2">
      <span class="subsection-label mb-0">Daftar Formasi</span>
      <div class="ml-auto">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-add-row">+ Tambah Baris</button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered align-middle mb-3" id="tbl-rows">
        <thead style="background:#f8fafc;">
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
              <select name="items[0][nama_formasi]" class="form-control sel-formasi" required>
                <option value="">-- Pilih Nama Formasi --</option>
                @foreach(($daftarFormasi ?? []) as $f)
                  <option value="{{ $f }}" @selected(old('items.0.nama_formasi')===$f)>{{ $f }}</option>
                @endforeach
              </select>
            </td>
            <td>
              <select name="items[0][jenjang_id]" class="form-control sel-jenjang" required>
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

    <div class="d-flex justify-content-end mt-4">
      <a href="{{ route('user.formasi.index') }}" class="btn btn-outline-secondary mr-2">Batal</a>
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Simpan
      </button>
    </div>
  </form>

  </div>
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
          <select name="items[${idx}][nama_formasi]" class="form-control sel-formasi" required>
            <option value="">-- Pilih Nama Formasi --</option>
            @foreach(($daftarFormasi ?? []) as $f)
              <option value="{{ $f }}">{{ $f }}</option>
            @endforeach
          </select>
        </td>
        <td>
          <select name="items[${idx}][jenjang_id]" class="form-control sel-jenjang" required>
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
