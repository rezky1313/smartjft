@extends('layouts.users.master')
@section('title', 'Buat Usulan Rekomendasi Formasi')

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

@if (session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<form method="post" action="{{ route('user.rekomendasi-formasi.store') }}" id="form-rf">
@csrf

{{-- ===================== STEP 1: JF & Unit Kerja ===================== --}}
<div class="preview-card mb-4">
  <div class="preview-header">
    <span class="preview-header-title">1. Jabatan Fungsional & Unit Kerja</span>
  </div>
  <div class="preview-body">
    <div class="form-group">
      <label class="font-weight-bold">Jabatan Fungsional</label>
      <select name="kode_jf" class="form-control" required>
        @foreach($jfTersedia as $kode => $label)
          <option value="{{ $kode }}">{{ $label }}</option>
        @endforeach
        <option value="" disabled>-- JF lain (rumus belum tersedia) --</option>
        @foreach($jfBelumTersedia as $namaJf)
          <option value="" disabled>{{ $namaJf }} (rumus belum tersedia)</option>
        @endforeach
      </select>
    </div>

    @if($unitKerja)
      {{-- Admin Unit: unit kerja otomatis terisi sesuai akun yang login --}}
      <div class="form-group">
        <label class="font-weight-bold">Unit Kerja</label>
        <input type="text" class="form-control" value="{{ $unitKerja->nama_unit_kerja }}" disabled>
        <small class="text-muted">
          Jenis Instansi: {{ $unitKerja->jenis_instansi ? ucfirst($unitKerja->jenis_instansi) : 'BELUM DILENGKAPI — hubungi Admin Pusbin' }}
        </small>
      </div>
    @else
      {{-- Admin Pusbin membantu input: pilih unit kerja lewat dropdown --}}
      <div class="form-group">
        <label class="font-weight-bold">Unit Kerja</label>
        <select name="unit_kerja_id" class="form-control" required>
          <option value="">-- Pilih Unit Kerja --</option>
          @foreach($unitKerjaList as $u)
            <option value="{{ $u->id }}" data-jenis-instansi="{{ $u->jenis_instansi ?? '' }}">
              {{ $u->nama_unit_kerja }} @if(!$u->jenis_instansi) (Jenis Instansi belum dilengkapi) @endif
            </option>
          @endforeach
        </select>
      </div>
    @endif

    <div class="form-group">
      <label class="font-weight-bold">Tahun Usulan</label>
      <input type="number" name="tahun" class="form-control" value="{{ old('tahun', $tahunDefault) }}" required>
    </div>
  </div>
</div>

{{-- ===================== STEP 2: Variabel Beban Kerja ===================== --}}
<div class="preview-card mb-4">
  <div class="preview-header">
    <span class="preview-header-title">2. Data Beban Kerja PKB</span>
    <span class="preview-header-subtitle d-block">Setara "Sheet1" pada Excel referensi</span>
  </div>
  <div class="preview-body">
    <div class="form-row">
      <div class="form-group col-md-4">
        <label class="font-weight-bold">Jumlah Kendaraan Bermotor Wajib Uji (KBWU)</label>
        <input type="number" min="0" name="jumlah_kbwu" class="form-control" value="{{ old('jumlah_kbwu') }}" required>
      </div>
      <div class="form-group col-md-4">
        <label class="font-weight-bold">Uji Pertama</label>
        <input type="number" min="0" name="uji_pertama" class="form-control" value="{{ old('uji_pertama') }}" required>
      </div>
      <div class="form-group col-md-4">
        <label class="font-weight-bold">Uji Reguler</label>
        <input type="number" min="0" name="uji_reguler" class="form-control" value="{{ old('uji_reguler') }}" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-3">
        <label class="font-weight-bold">Numpang Uji Masuk</label>
        <input type="number" min="0" name="numpang_uji_masuk" class="form-control" value="{{ old('numpang_uji_masuk', 0) }}">
      </div>
      <div class="form-group col-md-3">
        <label class="font-weight-bold">Numpang Uji Keluar</label>
        <input type="number" min="0" name="numpang_uji_keluar" class="form-control" value="{{ old('numpang_uji_keluar', 0) }}">
      </div>
      <div class="form-group col-md-3">
        <label class="font-weight-bold">Mutasi Masuk</label>
        <input type="number" min="0" name="mutasi_masuk" class="form-control" value="{{ old('mutasi_masuk', 0) }}">
      </div>
      <div class="form-group col-md-3">
        <label class="font-weight-bold">Mutasi Keluar</label>
        <input type="number" min="0" name="mutasi_keluar" class="form-control" value="{{ old('mutasi_keluar', 0) }}">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-4">
        <label class="font-weight-bold">Kendaraan BBM Bensin</label>
        <input type="number" min="0" name="bbm_bensin" class="form-control" value="{{ old('bbm_bensin', 0) }}">
      </div>
      <div class="form-group col-md-4">
        <label class="font-weight-bold">Kendaraan BBM Solar</label>
        <input type="number" min="0" name="bbm_solar" class="form-control" value="{{ old('bbm_solar', 0) }}">
      </div>
      <div class="form-group col-md-4">
        <label class="font-weight-bold">Hari Kerja</label>
        <select name="hari_kerja" class="form-control">
          <option value="5" {{ old('hari_kerja','5')=='5' ? 'selected' : '' }}>5 Hari</option>
          <option value="6" {{ old('hari_kerja')=='6' ? 'selected' : '' }}>6 Hari</option>
        </select>
        <small class="text-muted">Belum memengaruhi hasil kalkulasi saat ini, dicatat untuk pengembangan lanjutan</small>
      </div>
    </div>
    <div class="form-group">
      <label class="font-weight-bold">Angka Usulan Formasi (opsional, per jenjang)</label>
      <small class="text-muted d-block mb-2">Isi jika unit kerja punya angka usulan sendiri, untuk dibandingkan dengan hasil hitung sistem</small>
      <div class="form-row">
        @foreach($jenjangList as $j)
        <div class="col-md-3">
          <label>{{ ucfirst($j) }}</label>
          <input type="number" min="0" name="usulan_admin_unit[{{ $j }}]" class="form-control" value="{{ old('usulan_admin_unit.'.$j) }}">
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

{{-- ===================== STEP 3: Data Pegawai Existing (khusus Dishub) ===================== --}}
<div class="preview-card mb-4" id="card-pegawai-existing" style="{{ (!$unitKerja || $unitKerja->jenis_instansi !== 'dishub') ? 'display:none;' : '' }}">
  <div class="preview-header">
    <span class="preview-header-title">3. Data Pegawai PKB Existing</span>
  </div>
  <div class="preview-body">
    <p class="text-muted">Lengkapi data pegawai PKB yang sudah ada di unit kerja Anda. Data ini akan tersimpan permanen ke sistem Pegawai JFT.</p>
    <div class="table-responsive">
      <table class="table" id="tabel-pegawai-existing">
        <thead><tr><th>Nama</th><th>NIP</th><th>Jenjang</th><th></th></tr></thead>
        <tbody>
          <tr>
            <td><input type="text" name="pegawai[0][nama]" class="form-control"></td>
            <td><input type="text" name="pegawai[0][nip]" class="form-control"></td>
            <td>
              <select name="pegawai[0][jenjang]" class="form-control">
                @foreach($jenjangList as $j)
                  <option value="{{ $j }}">{{ ucfirst($j) }}</option>
                @endforeach
              </select>
            </td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBarisPegawai(this)">Hapus</button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" id="tambah-baris-pegawai">+ Tambah Pegawai</button>
  </div>
</div>

<div class="mb-4">
  <button type="submit" class="btn btn-primary">Simpan &amp; Hitung Otomatis</button>
  <a href="{{ route('user.rekomendasi-formasi.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>

</form>
@endsection

@push('scripts')
<script>
(function(){
  // ---- Tampilkan/sembunyikan Step 3 sesuai jenis_instansi unit kerja terpilih ----
  const cardPegawai = document.getElementById('card-pegawai-existing');
  const selectUnit = document.querySelector('select[name="unit_kerja_id"]');

  function toggleCardPegawai(jenisInstansi) {
    if (!cardPegawai) return;
    cardPegawai.style.display = (jenisInstansi === 'dishub') ? '' : 'none';
  }

  selectUnit?.addEventListener('change', function(){
    const opt = this.options[this.selectedIndex];
    toggleCardPegawai(opt?.dataset?.jenisInstansi);
  });

  // ---- Tambah/hapus baris pegawai dinamis (pola sama seperti modul Formasi) ----
  const tbl = document.querySelector('#tabel-pegawai-existing tbody');
  const btnAdd = document.getElementById('tambah-baris-pegawai');

  function renumber(){
    tbl.querySelectorAll('tr').forEach((tr, idx) => {
      tr.querySelectorAll('input,select').forEach(el => {
        el.name = el.name.replace(/pegawai\[\d+\]/, 'pegawai[' + idx + ']');
      });
    });
  }

  btnAdd?.addEventListener('click', function(){
    const idx = tbl.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="text" name="pegawai[${idx}][nama]" class="form-control"></td>
      <td><input type="text" name="pegawai[${idx}][nip]" class="form-control"></td>
      <td>
        <select name="pegawai[${idx}][jenjang]" class="form-control">
          @foreach($jenjangList as $j)
            <option value="{{ $j }}">{{ ucfirst($j) }}</option>
          @endforeach
        </select>
      </td>
      <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBarisPegawai(this)">Hapus</button></td>
    `;
    tbl.appendChild(tr);
    renumber();
  });

  window.hapusBarisPegawai = function(btn) {
    const all = tbl.querySelectorAll('tr');
    if (all.length === 1) {
      const tr = all[0];
      tr.querySelectorAll('input').forEach(i => i.value = '');
      tr.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    } else {
      btn.closest('tr').remove();
      renumber();
    }
  };
})();
</script>
@endpush
