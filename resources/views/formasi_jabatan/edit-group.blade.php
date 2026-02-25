@extends('layouts.users.master')
@section('title', $mode==='group' ? 'Edit Formasi (Grup)' : 'Edit Formasi Jabatan')

@section('isi')
@php
  // fallback agar tidak error jika variabel tidak dikirim
  $jenjang    = $jenjang ?? collect();
  $unitkerja  = $unitkerja ?? collect();
  $rows       = $rows ?? collect();
  $daftarFormasi = $daftarFormasi ?? [];
@endphp

<div class="container-fluid">
  <h4 class="mb-4">
    {{ $mode==='group' ? 'Edit Formasi (Unit & Tahun)' : 'Edit Formasi Jabatan' }}
  </h4>

  {{-- Notifikasi --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
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

  {{-- ====================== MODE: EDIT GROUP ====================== --}}
  @if(($mode ?? null) === 'group')
    <form method="POST" action="{{ route('user.formasi.update-group') }}" id="form-group">
      @csrf

      <div class="card mb-3">
        <div class="card-header">
          <h5 class="card-title mb-0">Informasi Umum</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-lg-6">
              <label class="form-label">Unit Kerja</label>
              <input type="hidden" name="unit_kerja_id" value="{{ $unit->no_rs }}">
              <input type="text" class="form-control" value="{{ $unit->nama_rumahsakit }}" disabled>
            </div>
            <div class="col-lg-6">
              <label class="form-label">Tahun Formasi</label>
              <input type="hidden" name="tahun_formasi" value="{{ $tahun }}">
              <input type="text" class="form-control" value="{{ $tahun }}" disabled>
            </div>
          </div>
        </div>
      </div>

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
                @forelse($rows as $i => $r)
                  <tr>
                    <td class="row-no"></td>
                    <td>
                      <input type="hidden" name="items[{{ $i }}][id]" value="{{ $r->id }}">
                      {{-- <select name="items[{{ $i }}][nama_formasi]" class="form-select sel-formasi" required>
                        <option value="">-- Pilih Nama Formasi --</option>
                        @foreach($daftarFormasi as $f)
                          <option value="{{ $f }}" @selected(old("items.$i.nama_formasi",$r->nama_formasi)===$f)>
                            {{ $f }}
                          </option>
                        @endforeach
                      </select> --}}

                      <select name="items[{{ $i }}][nama_formasi]" class="form-select sel-formasi" required>
                      <option value="">-- Pilih Nama Formasi --</option>

                      @php
                        $currRaw = $r->nama_formasi ?? '';
                        $curr    = trim($currRaw);
                        // bandingkan case-insensitive dan trim
                        $inList  = collect($daftarFormasi ?? [])->contains(function($v) use ($curr){
                          return mb_strtolower(trim($v)) === mb_strtolower($curr);
                        });
                      @endphp

                      {{-- Jika nilai di DB tidak ada di daftar resmi, tampilkan dulu sebagai opsi terpilih --}}
                      {{-- @if($curr !== '' && !$inList)
                        <option value="{{ $curr }}" selected>[Tidak ada di daftar] {{ $curr }}</option>
                      @endif --}}

                      @foreach($daftarFormasi as $f)
                        @php
                          $isSelected = mb_strtolower(trim(old("items.$i.nama_formasi", $curr))) === mb_strtolower(trim($f));
                        @endphp
                        <option value="{{ $f }}" @if($isSelected) selected @endif>{{ $f }}</option>
                      @endforeach
                    </select>
                    </td>
                    <td>
                      <select name="items[{{ $i }}][jenjang_id]" class="form-select sel-jenjang" required>
                        <option value="">-- Pilih Jenjang Jabatan --</option>
                        @foreach ($jenjang->groupBy('kategori') as $kategori => $items)
                          <optgroup label="{{ $kategori }}">
                            @foreach ($items as $item)
                              <option value="{{ $item->id }}" @selected(old("items.$i.jenjang_id",$r->jenjang_id)==$item->id)>
                                {{ $item->nama_jenjang }}
                              </option>
                            @endforeach
                          </optgroup>
                        @endforeach
                      </select>
                    </td>
                    <td>
                      <input type="number" min="0" name="items[{{ $i }}][kuota]" class="form-control"
                             value="{{ old("items.$i.kuota", (int)$r->kuota) }}" required>
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-outline-danger btn-del" title="Hapus baris">&times;</button>
                    </td>
                  </tr>
                @empty
                  {{-- jika tidak ada rows, sediakan satu baris kosong --}}
                  <tr>
                    <td class="row-no"></td>
                    <td>
                      <input type="hidden" name="items[0][id]" value="">
                      <select name="items[0][nama_formasi]" class="form-select sel-formasi" required>
                        <option value="">-- Pilih Nama Formasi --</option>
                        @foreach($daftarFormasi as $f)
                          <option value="{{ $f }}">{{ $f }}</option>
                        @endforeach
                      </select>
                    </td>
                    <td>
                      <select name="items[0][jenjang_id]" class="form-select sel-jenjang" required>
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
                    <td><input type="number" min="0" name="items[0][kuota]" class="form-control" value="0" required></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-del">&times;</button></td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="card-footer d-flex gap-2">
          <a href="{{ route('user.formasi.index') }}" class="btn btn-secondary">Kembali</a>
          <button class="btn btn-primary ms-auto">Simpan Perubahan</button>
        </div>
      </div>
    </form>

  {{-- ====================== MODE: EDIT SINGLE ====================== --}}
  @else
    <form action="{{ route('user.formasi.update', $formasi->id) }}" method="POST">
      @csrf
      @method('PUT')

      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Form Edit</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Nama Formasi</label>
            <select name="nama_formasi" id="nama_formasi" class="form-select select2" required>
              <option value="">-- Pilih Nama Formasi --</option>
              @foreach($daftarFormasi as $f)
                <option value="{{ $f }}" @selected(old('nama_formasi', $formasi->nama_formasi) === $f)>{{ $f }}</option>
              @endforeach
            </select>
            @error('nama_formasi') <small class="text-danger">{{ $message }}</small> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Jenjang Jabatan</label>
            <select name="jenjang_id" id="jenjang_id" class="form-select" required>
              <option value="">-- Pilih Jenjang Jabatan --</option>
              @foreach ($jenjang->groupBy('kategori') as $kategori => $items)
                <optgroup label="{{ $kategori }}">
                  @foreach ($items as $item)
                    <option value="{{ $item->id }}" @selected(old('jenjang_id', $formasi->jenjang_id) == $item->id)>
                      {{ $item->nama_jenjang ?? $item->kategori }}
                    </option>
                  @endforeach
                </optgroup>
              @endforeach
            </select>
            @error('jenjang_id') <small class="text-danger">{{ $message }}</small> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Unit Kerja</label>
            <select name="unit_kerja_id" id="unit_kerja_id" class="form-select select2" required>
              <option value="">-- Pilih Unit Kerja --</option>
              @foreach ($unitkerja as $unit)
                <option value="{{ $unit->no_rs }}" @selected(old('unit_kerja_id', $formasi->unit_kerja_id) == $unit->no_rs)>
                  {{ $unit->nama_rumahsakit }}
                </option>
              @endforeach
            </select>
            @error('unit_kerja_id') <small class="text-danger">{{ $message }}</small> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Kuota</label>
            <input type="number" name="kuota" id="kuota" class="form-control"
                   value="{{ old('kuota', $formasi->kuota) }}" min="0" required>
            @error('kuota') <small class="text-danger">{{ $message }}</small> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Tahun Formasi</label>
            <input type="text" name="tahun_formasi" id="tahun_formasi" class="form-control"
                   value="{{ old('tahun_formasi', $formasi->tahun_formasi) }}" required>
            @error('tahun_formasi') <small class="text-danger">{{ $message }}</small> @enderror
          </div>

          @isset($formasi->terisi)
            <div class="alert alert-info p-2">
              Terisi: <strong>{{ $formasi->terisi }}</strong> /
              Kuota: <strong>{{ $formasi->kuota }}</strong> —
              Sisa: <strong>{{ max($formasi->kuota - $formasi->terisi, 0) }}</strong>
            </div>
          @endisset
        </div>
        <div class="card-footer d-flex gap-2">
          <a href="{{ route('user.formasi.index') }}" class="btn btn-secondary">Kembali</a>
          <button class="btn btn-primary ms-auto">Perbarui</button>
        </div>
      </div>
    </form>
  @endif
</div>
@endsection

@push('styles')
  {{-- Select2 optional (hapus jika sudah ada di layout) --}}
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
  <style>
    #tbl-rows .row-no{ width:48px; text-align:center; }
  </style>
@endpush

@push('scripts')
  {{-- Select2 optional (hapus jika sudah ada di layout) --}}
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
  (function(){
    // Inisialisasi select2 pada halaman
    if (window.$ && $.fn.select2) {
      $('.select2, .sel-formasi, .sel-jenjang').select2({ width:'100%' });
    }

    // Khusus mode group: dynamic rows
    const tbl   = document.querySelector('#tbl-rows tbody');
    const btnAdd= document.querySelector('#btn-add-row');

    if (tbl && btnAdd){
      function initSelect2(scope){
        if (window.$ && $.fn.select2) {
          $(scope).find('.sel-formasi, .sel-jenjang').select2({ width:'100%' });
        }
      }
      function renumber(){
        tbl.querySelectorAll('tr').forEach((tr,idx)=>{
          tr.querySelector('.row-no').textContent = idx+1;
          tr.querySelectorAll('input,select').forEach(el=>{
            el.name = el.name.replace(/items\[\d+\]/, 'items['+idx+']');
          });
        });
      }
      btnAdd.addEventListener('click', ()=>{
        const idx = tbl.querySelectorAll('tr').length;
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td class="row-no"></td>
          <td>
            <input type="hidden" name="items[${idx}][id]" value="">
            <select name="items[${idx}][nama_formasi]" class="form-select sel-formasi" required>
              <option value="">-- Pilih Nama Formasi --</option>
              @foreach($daftarFormasi as $f)
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
          <td><input type="number" min="0" name="items[${idx}][kuota]" class="form-control" value="0" required></td>
          <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-del">&times;</button></td>
        `;
        tbl.appendChild(tr);
        renumber();
        initSelect2(tr);
      });

      tbl.addEventListener('click', (e)=>{
        if(e.target.closest('.btn-del')){
          const all = tbl.querySelectorAll('tr');
          if(all.length === 1){
            const tr = all[0];
            tr.querySelectorAll('input').forEach(i=> i.value = (i.type==='number' ? 0 : ''));
            tr.querySelectorAll('select').forEach(s=> s.selectedIndex = 0);
            if (window.$ && $.fn.select2) $(tr).find('select').val(null).trigger('change');
          } else {
            e.target.closest('tr').remove();
            renumber();
          }
        }
      });

      // init awal
      renumber();
    }
  })();
  </script>
@endpush
