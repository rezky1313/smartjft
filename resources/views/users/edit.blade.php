@extends('layouts.users.master')
@section('title','Pusbin JFT - ADMIN')
@section('isi')

<div class="container-fluid px-4">
    <div class="card mb-4">
        <div class="card-body">
           <h3>Edit Data Unit Kerja</h3>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Data Unit Kerja
        </div>

        @if ($errors->any())
            <div class="alert alert-danger mt-4">
                <strong>Whoops!</strong> Ada masalah dengan inputanmu.<br><br>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('user.unitkerja.update', $unitkerja) }}" method="POST" enctype="multipart/form-data" class="mt-4">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group ms-2">
                    <label for="nama_rumahsakit" class="form-label">Nama Unit Kerja:</label>
                    <input type="text" name="nama_rumahsakit" id="nama_rumahsakit" value="{{ $unitkerja->nama_rumahsakit }}" required class="form-control">
                    </div>
            <div class="form-group ms-2">
                <label for="alamat" class="form-label">Alamat:</label>
                <input type="text" name="alamat" id="alamat" value="{{ $unitkerja->alamat }}" required class="form-control">
            </div>
            <div class="form-group ms-2">
                <label for="no_telp" class="form-label">No. Telepon:</label>
                <input type="text" name="no_telp" id="no_telp" value="{{ $unitkerja->no_telp }}" required class="form-control">
            </div>
            {{-- <div class="form-group ms-2">
                <label for="jam_kerja" class="form-label">Jumlah JFT:</label>
                <input type="text" name="jam_kerja" id="jam_kerja" value="{{ $unitkerja->jam_kerja }}" required class="form-control">
            </div> --}}
            {{-- <div class="form-group ms-2">
                <label for="fasilitas" class="form-label">Kabupaten/Kota:</label>
                <input type="text" name="fasilitas" id="fasilitas" value="{{ $unitkerja->fasilitas }}" required class="form-control">
            </div> --}}


            <div class="ms-2">
  <label class="form-label">Provinsi</label>
  <select id="provinsiSelect" class="form-select">
    <option value="">-- Pilih Provinsi --</option>
    @foreach($provinces as $p)
      <option value="{{ $p->id }}" @selected(optional($unitkerja->regency->province ?? null)->id == $p->id)>
        {{ $p->name }}
      </option>
    @endforeach
  </select>
</div>

<div class="ms-2">
  <label class="form-label">Kabupaten/Kota</label>
  <select name="regency_id" id="regencySelect" class="form-select" required>
    @if(isset($regencies) && $regencies->count())
      @foreach($regencies as $r)
        <option value="{{ $r->id }}" @selected(old('regency_id', $unitkerja->regency_id ?? '') == $r->id)>
          {{ $r->type }} {{ $r->name }}
        </option>
      @endforeach
    @else
      <option value="">-- Pilih kab/kota --</option>
    @endif
  </select>
  @error('regency_id') <small class="text-danger">{{ $message }}</small> @enderror
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const prov = document.getElementById('provinsiSelect');
  const reg  = document.getElementById('regencySelect');
  prov?.addEventListener('change', async function() {
    reg.innerHTML = '<option value="">Memuat…</option>';
    if (!this.value) { reg.innerHTML = '<option value="">-- Pilih kab/kota --</option>'; return; }
    const url = '{{ route('user.wilayah.regencies', ':id') }}'.replace(':id', this.value);
    const res = await fetch(url);
    const data = await res.json();
    reg.innerHTML = '<option value="">-- Pilih kab/kota --</option>';
    data.forEach(x => {
      const opt = document.createElement('option');
      opt.value = x.id; opt.textContent = x.text;
      reg.appendChild(opt);
    });
  });
});
</script>
@endpush

<div class="ms-2">
  <label class="form-label">Matra</label>
  <select name="matra" class="form-select" required>
    <option value="">-- Pilih Matra --</option>
    @foreach (['Darat','Laut','Udara','Kereta'] as $opt)
      <option value="{{ $opt }}" @selected(old('matra', $unitkerja->matra)===$opt)>{{ $opt }}</option>
    @endforeach
  </select>
  @error('matra') <small class="text-danger">{{ $message }}</small> @enderror
</div>

<div class="ms-2">
  <label class="form-label">Instansi</label>
  <select name="instansi" class="form-select" required>
    <option value="">-- Pilih Instansi --</option>
    @foreach (['Pusat','Daerah'] as $opt)
      <option value="{{ $opt }}" @selected(old('instansi', $unitkerja->instansi)===$opt)>{{ $opt }}</option>
    @endforeach
  </select>
  @error('instansi') <small class="text-danger">{{ $message }}</small> @enderror
</div>



            <div class="form-group ms-2">
                <label for="latitude" class="form-label">Latitude:</label>
                <input type="text" name="latitude" id="latitude" value="{{ $unitkerja->latitude }}" required class="form-control">
            </div>
            <div class="form-group ms-2">
                <label for="longitude" class="form-label">Longitude:</label>
                <input type="text" name="longitude" id="longitude" value="{{ $unitkerja->longitude }}" required class="form-control">
            </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="col-md-12">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <div id="leafletMap-registration"></div>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-mb-4">
            <div class="row justify-content-center mt-3">
                <div class="com-md-6">
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
            </div>
        </div>
        </form>
    </div>
    </div>


@endsection
