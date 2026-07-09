@extends('layouts.users.master')

@section('title', 'Daftar Uji Kompetensi — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">
    <form action="{{ route('ujikom.permohonan.store') }}" method="POST" enctype="multipart/form-data" id="formPendaftaran">
      @csrf

      @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
      @endif

      {{-- ═══════════════════════════════════════════════════
           SECTION 1: INFORMASI PENDAFTARAN
      ═══════════════════════════════════════════════════ --}}
      <div class="card">
        <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-info-circle mr-2"></i>1. Informasi Pendaftaran</h3></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-{{ auth()->user()->hasRole('pemangku') ? '8' : '6' }}">
              <div class="form-group">
                <label>Jadwal Uji Kompetensi <span class="text-danger">*</span></label>
                <select name="ujikom_jadwal_id" id="jadwalSelect" class="form-control @error('ujikom_jadwal_id') is-invalid @enderror" required>
                  <option value="">-- Pilih Jadwal --</option>
                  @foreach ($jadwals as $j)
                  <option value="{{ $j->id }}"
                    {{ old('ujikom_jadwal_id', request('jadwal_id')) == $j->id ? 'selected' : '' }}>
                    {{ $j->judul }} — {{ $j->tanggal_mulai->format('d M Y') }}
                    @if ($j->matra) ({{ $j->matra }}) @endif
                  </option>
                  @endforeach
                </select>
                @error('ujikom_jadwal_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>

            @hasrole('pemangku')
            {{-- Pemangku: unit kerja dan jenis otomatis --}}
            <input type="hidden" name="unit_kerja_id"     value="{{ $sdmPemangku->unit_kerja_id }}">
            <input type="hidden" name="jenis_pendaftaran" value="mandiri">
            <div class="col-md-4">
              <div class="form-group">
                <label>Unit Kerja</label>
                <input type="text" class="form-control" value="{{ $sdmPemangku->unitKerja?->nama_unit_kerja ?? '-' }}" readonly>
              </div>
            </div>
            @else
            {{-- Admin / Admin Unit: dropdown pilih unit kerja + jenis --}}
            <div class="col-md-4">
              <div class="form-group">
                <label>Unit Kerja <span class="text-danger">*</span></label>
                <select name="unit_kerja_id" id="unitKerjaSelect" class="form-control @error('unit_kerja_id') is-invalid @enderror" required>
                  <option value="">-- Pilih Unit Kerja --</option>
                  @foreach ($unitKerjas as $u)
                  <option value="{{ $u->id }}" {{ old('unit_kerja_id') == $u->id ? 'selected' : '' }}>
                    {{ $u->nama_unit_kerja }}
                  </option>
                  @endforeach
                </select>
                @error('unit_kerja_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>Jenis <span class="text-danger">*</span></label>
                <select name="jenis_pendaftaran" class="form-control">
                  <option value="mandiri" {{ old('jenis_pendaftaran','mandiri') === 'mandiri' ? 'selected' : '' }}>Mandiri</option>
                  <option value="batch"   {{ old('jenis_pendaftaran') === 'batch'   ? 'selected' : '' }}>Batch</option>
                </select>
              </div>
            </div>
            @endhasrole
          </div>

          {{-- Card Info Jadwal (muncul setelah jadwal dipilih) --}}
          <div id="jadwalInfoCard" class="alert alert-info d-none mt-2" style="font-size:0.9rem;">
            <div class="row">
              <div class="col-md-4"><i class="fas fa-calendar-alt mr-1"></i> <span id="infoTanggal">-</span></div>
              <div class="col-md-4"><i class="fas fa-map-marker-alt mr-1"></i> <span id="infoTempat">-</span></div>
              <div class="col-md-4"><i class="fas fa-users mr-1"></i> Kuota: <span id="infoKuota">-</span> | Sisa: <strong id="infoSisa">-</strong></div>
            </div>
          </div>
        </div>
      </div>

      {{-- Info Formasi Unit Kerja --}}
      <div id="infoFormasiCard" class="card" style="display:none;">
        <div class="card-header bg-info py-2">
          <h3 class="card-title mb-0 text-white" style="font-size:0.95rem;">
            <i class="fas fa-table mr-2"></i>Info Formasi Unit Kerja — Tahun {{ date('Y') }}
          </h3>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th>Jabatan / Jenjang</th>
                  <th class="text-center" width="80">Kuota</th>
                  <th class="text-center" width="80">Terisi</th>
                  <th class="text-center" width="80">Sisa</th>
                  <th class="text-center" width="120">Status</th>
                </tr>
              </thead>
              <tbody id="formasiTbody">
                <tr><td colspan="5" class="text-center text-muted py-2">Memuat data...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- ═══════════════════════════════════════════════════
           SECTION 2: DAFTAR PESERTA
      ═══════════════════════════════════════════════════ --}}
      <div class="card">
        <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-users mr-2"></i>2. Daftar Peserta</h3></div>
        <div class="card-body">

          @hasrole('pemangku')
          {{-- ─── TAMPILAN PEMANGKU: data diri otomatis ─── --}}
          <div class="alert alert-info mb-3">
            <i class="fas fa-user-check mr-1"></i>
            Anda mendaftar atas nama diri sendiri. Data peserta diisi otomatis berdasarkan akun Anda.
          </div>

          {{-- Hidden inputs peserta[0] --}}
          <input type="hidden" name="peserta[0][pegawai_id]"     value="{{ $sdmPemangku->id }}">
          <input type="hidden" name="peserta[0][sisa_formasi]"   id="pemangkuSisaFormasi"   value="">
          <input type="hidden" name="peserta[0][status_formasi]" id="pemangkuStatusFormasi" value="tidak_tersedia">

          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead class="thead-light">
                <tr>
                  <th width="40">No</th>
                  <th>Nama Pegawai</th>
                  <th>NIP</th>
                  <th>Jabatan / Jenjang Saat Ini</th>
                  <th>Jabatan / Jenjang Tujuan</th>
                  <th>Status Formasi</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="text-center">1</td>
                  <td><strong>{{ $sdmPemangku->nama_lengkap }}</strong></td>
                  <td><small>{{ $sdmPemangku->nip }}</small></td>
                  <td id="pemangkuJabatanSekarang">
                    <span class="text-muted"><i class="fas fa-spinner fa-spin mr-1"></i>Memuat...</span>
                  </td>
                  <td>
                    <select name="peserta[0][formasi_tujuan_id]" id="jabatanTujuanSelect"
                            class="form-control form-control-sm" style="min-width:220px;">
                      <option value="">Memuat pilihan...</option>
                    </select>
                  </td>
                  <td id="pemangkuStatusFormasiDisplay">
                    <span class="badge badge-secondary">Mengecek...</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          @else
          {{-- ─── TAMPILAN ADMIN / ADMIN UNIT: pilih banyak pegawai ─── --}}
          <div class="row mb-3">
            <div class="col-md-5">
              <label>Pilih Pegawai</label>
              <select id="pegawaiSelect" class="form-control" style="width:100%;">
                <option value="">-- Pilih unit kerja di section 1 terlebih dahulu --</option>
              </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button type="button" id="tambahPesertaBtn" class="btn btn-success btn-sm btn-block">
                <i class="fas fa-plus mr-1"></i> Tambah
              </button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm" id="tabelPeserta">
              <thead class="thead-light">
                <tr>
                  <th width="40">No</th>
                  <th>Nama Pegawai</th>
                  <th>NIP</th>
                  <th>Jabatan / Jenjang Saat Ini</th>
                  <th>Jabatan / Jenjang Tujuan</th>
                  <th>Status Formasi</th>
                  <th width="60">Hapus</th>
                </tr>
              </thead>
              <tbody id="pesertaBody">
                <tr id="emptyRow"><td colspan="7" class="text-center text-muted">Belum ada peserta. Pilih dari dropdown di atas.</td></tr>
              </tbody>
            </table>
          </div>
          <div id="pesertaError" class="text-danger small mt-1 d-none">Minimal 1 peserta harus ditambahkan.</div>

          @endhasrole
        </div>
      </div>

      {{-- ═══════════════════════════════════════════════════
           SECTION 3: UPLOAD BERKAS PERSYARATAN
      ═══════════════════════════════════════════════════ --}}
      <div class="card" id="sectionBerkas">
        <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-paperclip mr-2"></i>3. Upload Berkas Persyaratan</h3></div>
        <div class="card-body">
          <div id="berkasPlaceholder" class="text-muted text-center py-3">
            Pilih jadwal terlebih dahulu untuk menampilkan persyaratan.
          </div>
          <div id="berkasContainer"></div>
        </div>
      </div>

      {{-- Footer Tombol --}}
      <div class="card">
        <div class="card-footer d-flex justify-content-between">
          <a href="{{ route('ujikom.permohonan.index') }}" class="btn btn-default">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
          </a>
          <div>
            <button type="submit" name="draft" class="btn btn-secondary mr-2">
              <i class="fas fa-save mr-1"></i> Simpan Draft
            </button>
            <button type="submit" name="ajukan" class="btn btn-primary">
              <i class="fas fa-paper-plane mr-1"></i> Simpan & Ajukan
            </button>
          </div>
        </div>
      </div>

    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
// ── State Global ────────────────────────────────────────────────────────────
let pesertaList     = [];
let persyaratanList = [];
let pesertaCounter  = 0;
let cachedPegawai   = [];

// Apakah user adalah pemangku (diset dari Blade)
const isPemangku = {{ auth()->user()->hasRole('pemangku') ? 'true' : 'false' }};

$(document).ready(function() {

// ─── INISIALISASI PEMANGKU ────────────────────────────────────────────────
@hasrole('pemangku')
// Pre-populate pesertaList dengan data pemangku
pesertaList.push({
  idx: 0,
  id: {{ $sdmPemangku->id }},
  nama: @json($sdmPemangku->nama_lengkap),
  nip: @json($sdmPemangku->nip),
  jabatan_sekarang: '-',
  jenjang_sekarang: '-',
  opsi_jabatan: [],
  sisa_formasi: null,
  status_formasi: 'tidak_tersedia',
  formasi_label: 'Mengecek...',
  formasi_badge: 'secondary',
});

// Auto-load data jabatan tujuan pemangku
$.ajax({
  url: '{{ route("ujikom.permohonan.get-jabatan-tujuan") }}',
  method: 'GET',
  data: { pegawai_id: {{ $sdmPemangku->id }} },
  success: function(res) {
    // Update tampilan jabatan sekarang
    $('#pemangkuJabatanSekarang').html(
      '<small>' + res.jabatan_sekarang + ' / ' + res.jenjang_sekarang + '</small>'
    );

    // Update hidden inputs
    $('#pemangkuSisaFormasi').val(res.sisa);
    $('#pemangkuStatusFormasi').val(res.status);

    // Update badge status formasi
    const badgeClass = res.badge;
    $('#pemangkuStatusFormasiDisplay').html(
      '<span class="badge badge-' + badgeClass + '">' + res.label + '</span>'
    );

    // Populate dropdown jabatan tujuan
    const sel = $('#jabatanTujuanSelect');
    sel.empty().append('<option value="">-- Pilih Jabatan Tujuan --</option>');
    (res.opsi_jabatan || []).forEach(function(group) {
      const og = $('<optgroup>').attr('label', group.label);
      (group.options || []).forEach(function(opt) {
        const label = opt.text + (opt.is_rekomendasi ? ' ⭐' : '');
        og.append(new Option(label, opt.id, opt.is_rekomendasi, opt.is_rekomendasi));
      });
      sel.append(og);
    });

    // Tampilkan pesan khusus jika ada
    if (res.pesan_khusus) {
      $('#jabatanTujuanSelect').after(
        '<small class="text-warning d-block mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>' + res.pesan_khusus + '</small>'
      );
    }

    // Update pesertaList state
    pesertaList[0].jabatan_sekarang = res.jabatan_sekarang;
    pesertaList[0].jenjang_sekarang = res.jenjang_sekarang;
    pesertaList[0].sisa_formasi     = res.sisa;
    pesertaList[0].status_formasi   = res.status;
    pesertaList[0].formasi_label    = res.label;
    pesertaList[0].formasi_badge    = res.badge;

    // Rebuild berkas jika jadwal sudah dipilih
    rebuildBerkasSection();
  },
  error: function() {
    $('#pemangkuJabatanSekarang').html('<span class="text-danger">Gagal memuat data.</span>');
  }
});

// Auto-load formasi info untuk unit kerja pemangku
$.ajax({
  url: '{{ route("ujikom.permohonan.get-formasi") }}',
  method: 'GET',
  data: { unit_kerja_id: {{ $sdmPemangku->unit_kerja_id }} },
  success: function(response) {
    const tbody = $('#formasiTbody');
    tbody.empty();
    if (!response.data || response.data.length === 0) {
      tbody.append('<tr><td colspan="5" class="text-center text-muted">Belum ada data formasi.</td></tr>');
    } else {
      $.each(response.data, function(i, f) {
        const badgeClass  = f.status === 'tersedia' ? 'success' : (f.status === 'penuh' ? 'warning' : 'danger');
        const labelStatus = f.status === 'tersedia' ? 'Tersedia' : (f.status === 'penuh' ? 'Penuh' : 'Over Kuota');
        const sisaClass   = f.sisa < 0 ? 'text-danger font-weight-bold' : (f.sisa === 0 ? 'text-warning font-weight-bold' : '');
        tbody.append(`<tr>
          <td>${f.jabatan}</td>
          <td class="text-center">${f.kuota}</td>
          <td class="text-center">${f.terisi}</td>
          <td class="text-center ${sisaClass}">${f.sisa}</td>
          <td class="text-center"><span class="badge badge-${badgeClass}">${labelStatus}</span></td>
        </tr>`);
      });
    }
    $('#infoFormasiCard').show();
  }
});
@endhasrole

// ─── SELECT2 PEGAWAI (non-pemangku) ──────────────────────────────────────
@if(!auth()->user()->hasRole('pemangku'))
$('#pegawaiSelect').select2({
  theme: 'bootstrap4',
  placeholder: '-- Pilih unit kerja di section 1 terlebih dahulu --',
  width: '100%',
  allowClear: true,
});

// Unit Kerja Berubah → Load pegawai + formasi
$('#unitKerjaSelect').on('change', function() {
  const unitKerjaId = $(this).val();
  cachedPegawai = [];

  if (!unitKerjaId) {
    const sel = $('#pegawaiSelect');
    if (sel.hasClass('select2-hidden-accessible')) sel.select2('destroy');
    sel.empty().append('<option value="">-- Pilih unit kerja di section 1 terlebih dahulu --</option>');
    sel.select2({ theme: 'bootstrap4', placeholder: '-- Pilih unit kerja di section 1 terlebih dahulu --', width: '100%', allowClear: true });
    $('#infoFormasiCard').hide();
    return;
  }

  $.ajax({
    url: '{{ route("ujikom.permohonan.get-pegawai") }}',
    method: 'GET',
    data: { unit_kerja_id: unitKerjaId },
    success: function(response) {
      cachedPegawai = response.results || [];
      const select = $('#pegawaiSelect');
      if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
      select.empty().append('<option value="">-- Pilih Pegawai --</option>');
      $.each(cachedPegawai, function(i, p) {
        select.append(new Option(p.text, p.id, false, false));
      });
      select.select2({ theme: 'bootstrap4', placeholder: '-- Pilih Pegawai --', width: '100%', allowClear: true });
    },
    error: function(xhr) { console.error('Gagal load pegawai:', xhr.responseText); }
  });

  $.ajax({
    url: '{{ route("ujikom.permohonan.get-formasi") }}',
    method: 'GET',
    data: { unit_kerja_id: unitKerjaId },
    success: function(response) {
      const tbody = $('#formasiTbody');
      tbody.empty();
      if (!response.data || response.data.length === 0) {
        tbody.append('<tr><td colspan="5" class="text-center text-muted">Belum ada data formasi untuk unit kerja ini tahun {{ date("Y") }}.</td></tr>');
      } else {
        $.each(response.data, function(i, f) {
          const badgeClass  = f.status === 'tersedia' ? 'success' : (f.status === 'penuh' ? 'warning' : 'danger');
          const labelStatus = f.status === 'tersedia' ? 'Tersedia' : (f.status === 'penuh' ? 'Penuh' : 'Over Kuota');
          const sisaClass   = f.sisa < 0 ? 'text-danger font-weight-bold' : (f.sisa === 0 ? 'text-warning font-weight-bold' : '');
          tbody.append(`<tr>
            <td>${f.jabatan}</td>
            <td class="text-center">${f.kuota}</td>
            <td class="text-center">${f.terisi}</td>
            <td class="text-center ${sisaClass}">${f.sisa}</td>
            <td class="text-center"><span class="badge badge-${badgeClass}">${labelStatus}</span></td>
          </tr>`);
        });
      }
      $('#infoFormasiCard').show();
    }
  });
});
@endif

// ─── JADWAL BERUBAH → Load info jadwal + persyaratan ──────────────────────
$('#jadwalSelect').on('change', function() {
  const jadwalId = $(this).val();
  if (!jadwalId) {
    $('#jadwalInfoCard').addClass('d-none');
    persyaratanList = [];
    rebuildBerkasSection();
    return;
  }

  $.get('{{ url("ujikom/pendaftaran/jadwal-info") }}/' + jadwalId, function(data) {
    $('#infoTanggal').text(data.tanggal_mulai + ' — ' + data.tanggal_selesai);
    $('#infoTempat').text(data.tempat);
    $('#infoKuota').text(data.kuota);
    $('#infoSisa').text(data.sisa_kuota);
    $('#jadwalInfoCard').removeClass('d-none');

    persyaratanList = data.persyaratan;
    rebuildBerkasSection();
  });
});

// ─── AUTO-SELECT JADWAL jika ada ?jadwal_id di URL ────────────────────────
@php $jadwalPreselect = request('jadwal_id'); @endphp
@if ($jadwalPreselect)
  const preJadwalId = {{ (int) $jadwalPreselect }};
  if (preJadwalId) {
    $('#jadwalSelect').val(preJadwalId).trigger('change');
  }
@endif

// ─── TAMBAH PESERTA (non-pemangku) ───────────────────────────────────────
@if(!auth()->user()->hasRole('pemangku'))
$('#tambahPesertaBtn').on('click', function() {
  const pegawaiId = $('#pegawaiSelect').val();
  if (!pegawaiId) { alert('Pilih pegawai terlebih dahulu.'); return; }
  if (pesertaList.find(p => p.id == pegawaiId)) { alert('Pegawai sudah ada di daftar peserta.'); return; }

  const pegawai = cachedPegawai.find(p => p.id == pegawaiId);
  if (!pegawai) return;

  $.ajax({
    url: '{{ route("ujikom.permohonan.get-jabatan-tujuan") }}',
    method: 'GET',
    data: { pegawai_id: pegawaiId },
    success: function(res) {
      const idx = pesertaCounter++;
      pesertaList.push({
        idx,
        id: pegawai.id,
        nama: pegawai.nama,
        nip: pegawai.nip,
        jabatan_sekarang: res.jabatan_sekarang,
        jenjang_sekarang: res.jenjang_sekarang,
        opsi_jabatan: res.opsi_jabatan,
        pesan_khusus: res.pesan_khusus,
        sisa_formasi: res.sisa,
        status_formasi: res.status,
        formasi_label: res.label,
        formasi_badge: res.badge,
      });
      renderPesertaTable();
      rebuildBerkasSection();
      $('#pegawaiSelect').val('').trigger('change');
    },
    error: function() { alert('Gagal mengambil data jabatan tujuan.'); }
  });
});
@endif

// ─── Render Tabel Peserta (non-pemangku) ─────────────────────────────────
function renderPesertaTable() {
  const tbody = $('#pesertaBody');
  tbody.empty();

  if (pesertaList.length === 0) {
    tbody.append('<tr id="emptyRow"><td colspan="7" class="text-center text-muted">Belum ada peserta.</td></tr>');
    return;
  }

  pesertaList.forEach((p, i) => {
    let selectHtml = `<select name="peserta[${p.idx}][formasi_tujuan_id]" class="form-control form-control-sm" style="min-width:220px;">
      <option value="">-- Pilih --</option>`;
    (p.opsi_jabatan || []).forEach(group => {
      selectHtml += `<optgroup label="${group.label}">`;
      (group.options || []).forEach(opt => {
        const sel = opt.is_rekomendasi ? 'selected' : '';
        const star = opt.is_rekomendasi ? ' ⭐' : '';
        selectHtml += `<option value="${opt.id}" ${sel}>${opt.text}${star}</option>`;
      });
      selectHtml += `</optgroup>`;
    });
    selectHtml += `</select>`;
    if (p.pesan_khusus) {
      selectHtml += `<br><small class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>${p.pesan_khusus}</small>`;
    }

    tbody.append(`
      <tr>
        <td class="text-center">${i + 1}</td>
        <td>${p.nama}
          <input type="hidden" name="peserta[${p.idx}][pegawai_id]"     value="${p.id}">
          <input type="hidden" name="peserta[${p.idx}][sisa_formasi]"   value="${p.sisa_formasi ?? ''}">
          <input type="hidden" name="peserta[${p.idx}][status_formasi]" value="${p.status_formasi}">
        </td>
        <td><small>${p.nip}</small></td>
        <td><small>${p.jabatan_sekarang} / ${p.jenjang_sekarang}</small></td>
        <td>${selectHtml}</td>
        <td><span class="badge badge-${p.formasi_badge}">${p.formasi_label}</span></td>
        <td class="text-center">
          <button type="button" class="btn btn-danger btn-xs hapus-peserta" data-id="${p.id}">
            <i class="fas fa-times"></i>
          </button>
        </td>
      </tr>
    `);
  });
}

// ─── Hapus Peserta ────────────────────────────────────────────────────────
$(document).on('click', '.hapus-peserta', function() {
  const pid = $(this).data('id');
  pesertaList = pesertaList.filter(p => p.id != pid);
  renderPesertaTable();
  rebuildBerkasSection();
});

// ─── Rebuild Bagian Upload Berkas ─────────────────────────────────────────
function rebuildBerkasSection() {
  const container = $('#berkasContainer');
  container.empty();

  if (persyaratanList.length === 0 || pesertaList.length === 0) {
    $('#berkasPlaceholder').removeClass('d-none');
    container.hide();
    return;
  }

  $('#berkasPlaceholder').addClass('d-none');
  container.show();

  persyaratanList.forEach(syarat => {
    let html = `
      <div class="card card-outline card-secondary mb-3">
        <div class="card-header py-2">
          <strong>${syarat.nama_syarat}</strong>
          ${syarat.file_contoh ? `<a href="${syarat.file_contoh}" target="_blank" class="btn btn-xs btn-outline-primary ml-2"><i class="fas fa-eye mr-1"></i>Lihat Contoh</a>` : ''}
        </div>
        <div class="card-body p-2">
          <table class="table table-sm table-bordered mb-0">
            <thead class="thead-light"><tr><th>Peserta</th><th>Upload File <small class="text-muted">(PDF/DOC/IMG, maks 2MB)</small></th></tr></thead>
            <tbody>`;

    pesertaList.forEach(p => {
      html += `
        <tr>
          <td style="width:40%">${p.nama}<br><small class="text-muted">${p.nip}</small></td>
          <td><input type="file" name="berkas[${p.idx}][${syarat.id}]" class="form-control-file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="font-size:0.85rem;"></td>
        </tr>`;
    });

    html += `</tbody></table></div></div>`;
    container.append(html);
  });
}

// ─── Validasi sebelum submit ──────────────────────────────────────────────
$('#formPendaftaran').on('submit', function(e) {
  if (!isPemangku && pesertaList.length === 0) {
    e.preventDefault();
    $('#pesertaError').removeClass('d-none');
    $('html,body').animate({ scrollTop: $('#tabelPeserta').offset().top - 100 }, 400);
  }
});

}); // end $(document).ready
</script>
@endpush
