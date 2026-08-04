@extends('layouts.users.master')
@section('title', 'Edit Usulan Rekomendasi Formasi')

@section('isi')

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

<div class="preview-card mb-4">
  <div class="preview-header">
    <span class="preview-header-title">Edit Usulan — {{ $usulan->unitKerja->nama_unit_kerja ?? '-' }}</span>
    <span class="preview-header-subtitle d-block">Tahun {{ $usulan->tahun }} &middot; Ubah variabel beban kerja lalu hitung ulang otomatis</span>
  </div>
  <div class="preview-body">

    @if($usulan->jenis_instansi === 'dishub')
      <div class="alert alert-light border mb-3" style="font-size:12px;">
        <i class="fas fa-info-circle mr-1"></i>
        Data pegawai yang sudah diupload sebelumnya tidak diubah lewat form ini — data itu sudah tersimpan permanen di modul Pegawai JFT.
      </div>
    @endif

    <form method="post" action="{{ route('user.rekomendasi-formasi.update', $usulan->id) }}">
      @csrf
      @method('PUT')

      <div class="form-row">
        <div class="form-group col-md-4">
          <label class="font-weight-bold">Jumlah Kendaraan Bermotor Wajib Uji (KBWU)</label>
          <input type="number" min="0" name="jumlah_kbwu" class="form-control" value="{{ old('jumlah_kbwu', $usulan->variabel->jumlah_kbwu) }}" required>
        </div>
        <div class="form-group col-md-4">
          <label class="font-weight-bold">Uji Pertama</label>
          <input type="number" min="0" name="uji_pertama" class="form-control" value="{{ old('uji_pertama', $usulan->variabel->uji_pertama) }}" required>
        </div>
        <div class="form-group col-md-4">
          <label class="font-weight-bold">Uji Reguler</label>
          <input type="number" min="0" name="uji_reguler" class="form-control" value="{{ old('uji_reguler', $usulan->variabel->uji_reguler) }}" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-3">
          <label class="font-weight-bold">Numpang Uji Masuk</label>
          <input type="number" min="0" name="numpang_uji_masuk" class="form-control" value="{{ old('numpang_uji_masuk', $usulan->variabel->numpang_uji_masuk) }}">
        </div>
        <div class="form-group col-md-3">
          <label class="font-weight-bold">Numpang Uji Keluar</label>
          <input type="number" min="0" name="numpang_uji_keluar" class="form-control" value="{{ old('numpang_uji_keluar', $usulan->variabel->numpang_uji_keluar) }}">
        </div>
        <div class="form-group col-md-3">
          <label class="font-weight-bold">Mutasi Masuk</label>
          <input type="number" min="0" name="mutasi_masuk" class="form-control" value="{{ old('mutasi_masuk', $usulan->variabel->mutasi_masuk) }}">
        </div>
        <div class="form-group col-md-3">
          <label class="font-weight-bold">Mutasi Keluar</label>
          <input type="number" min="0" name="mutasi_keluar" class="form-control" value="{{ old('mutasi_keluar', $usulan->variabel->mutasi_keluar) }}">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-4">
          <label class="font-weight-bold">Kendaraan BBM Bensin</label>
          <input type="number" min="0" name="bbm_bensin" class="form-control" value="{{ old('bbm_bensin', $usulan->variabel->bbm_bensin) }}">
        </div>
        <div class="form-group col-md-4">
          <label class="font-weight-bold">Kendaraan BBM Solar</label>
          <input type="number" min="0" name="bbm_solar" class="form-control" value="{{ old('bbm_solar', $usulan->variabel->bbm_solar) }}">
        </div>
        <div class="form-group col-md-4">
          <label class="font-weight-bold">Hari Kerja</label>
          <select name="hari_kerja" class="form-control">
            <option value="5" {{ old('hari_kerja', $usulan->variabel->hari_kerja)=='5' ? 'selected' : '' }}>5 Hari</option>
            <option value="6" {{ old('hari_kerja', $usulan->variabel->hari_kerja)=='6' ? 'selected' : '' }}>6 Hari</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="font-weight-bold">Angka Usulan Formasi (opsional, per jenjang)</label>
        <div class="form-row">
          @foreach($jenjangList as $j)
          @php $hasilJenjang = $usulan->hasil->firstWhere('jenjang', $j); @endphp
          <div class="col-md-3">
            <label>{{ ucfirst($j) }}</label>
            <input type="number" min="0" name="usulan_admin_unit[{{ $j }}]" class="form-control"
                   value="{{ old('usulan_admin_unit.'.$j, $hasilJenjang->usulan_admin_unit ?? '') }}">
          </div>
          @endforeach
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary">Simpan &amp; Hitung Ulang</button>
        <a href="{{ route('user.rekomendasi-formasi.show', $usulan->id) }}" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>
@endsection
