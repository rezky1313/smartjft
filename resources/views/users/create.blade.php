@extends('layouts.users.master')
@include('layouts.component.leaflet-assets')
@section('title','Pusbin JFT - ADMIN')
@section('isi')

@php
  $provinces = $provinces ?? collect();
  $regencies = $regencies ?? collect();
  $unitKerja = $unitKerja ?? null;
@endphp

<div class="preview-card">
  <div class="preview-header">
    <span class="preview-header-title">Tambah Unit Kerja</span>
    <span class="preview-header-subtitle d-block">Lengkapi data di bawah ini</span>
  </div>
  <div class="preview-body">

    @if ($errors->any())
    <div class="alert alert-danger">
      <strong>Whoops!</strong> Ada masalah dengan inputanmu.<br><br>
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <form action="{{ route('user.unitkerja.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="form-row">
        <div class="col-md-6">

          <span class="subsection-label">Informasi Dasar</span>
          <div class="form-group">
            <label for="nama_unit_kerja" class="font-weight-bold">Nama Unit Kerja <span class="text-danger">*</span></label>
            <input type="text" name="nama_unit_kerja" id="nama_unit_kerja" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="alamat" class="font-weight-bold">Alamat <span class="text-danger">*</span></label>
            <input type="text" name="alamat" id="alamat" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="no_telp" class="font-weight-bold">No. Telepon <span class="text-danger">*</span></label>
            <input type="text" name="no_telp" id="no_telp" class="form-control" required>
          </div>

          <hr class="my-4">

          <span class="subsection-label">Lokasi</span>
          <div class="form-group">
            <label class="font-weight-bold">Provinsi</label>
            <select id="provinsiSelect" class="form-control">
              <option value="">-- Pilih Provinsi --</option>
              @foreach($provinces as $p)
                <option value="{{ $p->id }}"
                  @selected(optional($unitKerja->regency->province ?? null)->id == $p->id)>{{ $p->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Kabupaten/Kota <span class="text-danger">*</span></label>
            <select name="regency_id" id="regencySelect" class="form-control" required>
              @if($regencies->count())
                @foreach($regencies as $r)
                  <option value="{{ $r->id }}" @selected(old('regency_id', $unitKerja->regency_id ?? '') == $r->id)>
                    {{ $r->type }} {{ $r->name }}
                  </option>
                @endforeach
              @else
                <option value="">-- Pilih kab/kota --</option>
              @endif
            </select>
            @error('regency_id') <small class="text-danger">{{ $message }}</small> @enderror
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label class="font-weight-bold">Matra <span class="text-danger">*</span></label>
              <select name="matra" class="form-control" required>
                <option value="">-- Pilih Matra --</option>
                @foreach (['Darat','Laut','Udara','Kereta'] as $opt)
                  <option value="{{ $opt }}" @selected(old('matra')===$opt)>{{ $opt }}</option>
                @endforeach
              </select>
              @error('matra') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
            <div class="form-group col-md-6">
              <label class="font-weight-bold">Instansi <span class="text-danger">*</span></label>
              <select name="instansi" class="form-control" required>
                <option value="">-- Pilih Instansi --</option>
                @foreach (['Pusat','Daerah'] as $opt)
                  <option value="{{ $opt }}" @selected(old('instansi')===$opt)>{{ $opt }}</option>
                @endforeach
              </select>
              @error('instansi') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="latitude" class="font-weight-bold">Latitude</label>
              <input type="text" name="latitude" id="latitude" placeholder="-7.3811577" class="form-control">
            </div>
            <div class="form-group col-md-6">
              <label for="longitude" class="font-weight-bold">Longitude</label>
              <input type="text" name="longitude" id="longitude" placeholder="109.2550945" class="form-control">
            </div>
          </div>

        </div>

        <div class="col-md-6">
          <span class="subsection-label">Klik Peta untuk Isi Koordinat</span>
          <div id="leafletMap-registration" style="height:400px; border-radius:8px; overflow:hidden;"></div>
        </div>
      </div>

      <div class="d-flex justify-content-end mt-4">
        <a href="{{ route('user.unitkerja.index') }}" class="btn btn-outline-secondary mr-2">Batal</a>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Simpan
        </button>
      </div>
    </form>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const prov = document.getElementById('provinsiSelect');
  const reg  = document.getElementById('regencySelect');

  // hindari masalah placeholder di route()
  const baseUrl = @json(url('/user/wilayah/regencies')); // -> "/user/wilayah/regencies"

  prov?.addEventListener('change', async function() {
    reg.innerHTML = '<option value="">Memuat…</option>';

    if (!this.value) { reg.innerHTML = '<option value="">-- Pilih kab/kota --</option>'; return; }

    const url = `${baseUrl}/${this.value}`;
    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();

      reg.innerHTML = '<option value="">-- Pilih kab/kota --</option>';
      (data || []).forEach(x => {
        const opt = document.createElement('option');
        opt.value = x.id;
        opt.textContent = x.text;
        reg.appendChild(opt);
      });
    } catch (e) {
      console.error(e);
      reg.innerHTML = '<option value="">Gagal memuat</option>';
    }
  });
});
</script>

@endsection
