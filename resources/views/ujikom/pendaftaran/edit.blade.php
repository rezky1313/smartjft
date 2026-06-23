@extends('layouts.users.master')

@section('title', 'Edit Pendaftaran — ' . $pendaftaran->kode_pendaftaran)

@section('isi')
<div class="row">
  <div class="col-12">
    <form action="{{ route('ujikom.permohonan.update', $pendaftaran->id) }}" method="POST" enctype="multipart/form-data" id="formPendaftaran">
      @csrf @method('PUT')

      @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
      @endif

      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Menyimpan perubahan akan <strong>menghapus dan membuat ulang</strong> semua peserta dan berkas yang sudah ada.
        Upload ulang berkas yang diperlukan.
      </div>

      {{-- SECTION 1: Info Pendaftaran --}}
      <div class="card">
        <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-info-circle mr-2"></i>1. Informasi Pendaftaran</h3></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Jadwal Uji Kompetensi <span class="text-danger">*</span></label>
                <select name="ujikom_jadwal_id" id="jadwalSelect" class="form-control" required>
                  <option value="">-- Pilih Jadwal --</option>
                  @foreach ($jadwals as $j)
                  <option value="{{ $j->id }}" {{ $pendaftaran->ujikom_jadwal_id == $j->id ? 'selected' : '' }}>
                    {{ $j->judul }} — {{ $j->tanggal_mulai->format('d M Y') }}
                  </option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Unit Kerja <span class="text-danger">*</span></label>
                <select name="unit_kerja_id" id="unitKerjaSelect" class="form-control" required>
                  <option value="">-- Pilih Unit Kerja --</option>
                  @foreach ($unitKerjas as $u)
                  <option value="{{ $u->no_rs }}" {{ $pendaftaran->unit_kerja_id == $u->no_rs ? 'selected' : '' }}>
                    {{ $u->nama_rumahsakit }}
                  </option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>Jenis</label>
                <select name="jenis_pendaftaran" class="form-control">
                  <option value="mandiri" {{ $pendaftaran->jenis_pendaftaran === 'mandiri' ? 'selected' : '' }}>Mandiri</option>
                  <option value="batch"   {{ $pendaftaran->jenis_pendaftaran === 'batch'   ? 'selected' : '' }}>Batch</option>
                </select>
              </div>
            </div>
          </div>
          <div id="jadwalInfoCard" class="alert alert-info mt-2" style="font-size:0.9rem;">
            <div class="row">
              <div class="col-md-4"><i class="fas fa-calendar-alt mr-1"></i> <span id="infoTanggal">{{ $pendaftaran->jadwal?->tanggal_mulai?->format('d F Y') ?? '-' }} — {{ $pendaftaran->jadwal?->tanggal_selesai?->format('d F Y') ?? '' }}</span></div>
              <div class="col-md-4"><i class="fas fa-map-marker-alt mr-1"></i> <span id="infoTempat">{{ $pendaftaran->jadwal?->tempat ?? '-' }}</span></div>
              <div class="col-md-4"><i class="fas fa-users mr-1"></i> Kuota: <span id="infoKuota">{{ $pendaftaran->jadwal?->kuota ?? '-' }}</span></div>
            </div>
          </div>
        </div>
      </div>

      {{-- SECTION 2: Peserta --}}
      <div class="card">
        <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-users mr-2"></i>2. Daftar Peserta</h3></div>
        <div class="card-body">
          <div class="alert alert-info py-2" style="font-size:0.85rem;">
            <i class="fas fa-info-circle mr-1"></i> Peserta sebelumnya:
            @foreach ($pendaftaran->peserta as $p)
              <span class="badge badge-secondary ml-1">{{ $p->pegawai?->nama ?? '-' }}</span>
            @endforeach
          </div>
          <div class="row mb-3">
            <div class="col-md-5">
              <select id="pegawaiSelect" class="form-control" style="width:100%;"></select>
            </div>
            <div class="col-md-2 d-flex align-items-center">
              <button type="button" id="tambahPesertaBtn" class="btn btn-success btn-sm btn-block">
                <i class="fas fa-plus mr-1"></i> Tambah
              </button>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered table-sm" id="tabelPeserta">
              <thead class="thead-light">
                <tr><th width="40">No</th><th>Nama Pegawai</th><th>NIP</th><th>Jabatan / Jenjang</th><th>Status Formasi</th><th width="60">Hapus</th></tr>
              </thead>
              <tbody id="pesertaBody">
                <tr id="emptyRow"><td colspan="6" class="text-center text-muted">Belum ada peserta.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- SECTION 3: Berkas --}}
      <div class="card" id="sectionBerkas">
        <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-paperclip mr-2"></i>3. Upload Berkas Persyaratan</h3></div>
        <div class="card-body">
          <div id="berkasPlaceholder" class="text-muted text-center py-3">
            Pilih jadwal untuk menampilkan persyaratan.
          </div>
          <div id="berkasContainer"></div>
        </div>
      </div>

      <div class="card">
        <div class="card-footer d-flex justify-content-between">
          <a href="{{ route('ujikom.permohonan.show', $pendaftaran->id) }}" class="btn btn-default">
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
let pesertaList     = [];
let persyaratanList = [];
let pesertaCounter  = 0;

$('#pegawaiSelect').select2({
  theme: 'bootstrap4', placeholder: '-- Pilih Pegawai --', width: '100%', allowClear: true,
  ajax: {
    url: '{{ route("ujikom.permohonan.pegawai-list") }}',
    data: function(p) { return { unit_kerja_id: $('#unitKerjaSelect').val(), q: p.term }; },
    processResults: function(d) { return { results: d }; },
    delay: 250,
  }
});

// Load jadwal info on page load (jadwal sudah pre-selected)
$(document).ready(function() {
  const jadwalId = $('#jadwalSelect').val();
  if (jadwalId) loadJadwalInfo(jadwalId);
});

$('#jadwalSelect').on('change', function() {
  loadJadwalInfo($(this).val());
});

function loadJadwalInfo(jadwalId) {
  if (!jadwalId) { persyaratanList = []; rebuildBerkasSection(); return; }
  $.get('{{ url("ujikom/pendaftaran/jadwal-info") }}/' + jadwalId, function(data) {
    $('#infoTanggal').text(data.tanggal_mulai + ' — ' + data.tanggal_selesai);
    $('#infoTempat').text(data.tempat);
    $('#infoKuota').text(data.kuota);
    $('#jadwalInfoCard').removeClass('d-none');
    persyaratanList = data.persyaratan;
    rebuildBerkasSection();
  });
}

$('#unitKerjaSelect').on('change', function() {
  $('#pegawaiSelect').val(null).trigger('change');
});

$('#tambahPesertaBtn').on('click', function() {
  const selected = $('#pegawaiSelect').select2('data');
  if (!selected.length || !selected[0].id) { alert('Pilih pegawai terlebih dahulu.'); return; }
  const pegawai = selected[0];
  if (pesertaList.find(p => p.id == pegawai.id)) { alert('Pegawai sudah ada di daftar.'); return; }
  $.get('{{ url("ujikom/pendaftaran/cek-formasi") }}/' + pegawai.id, function(formasi) {
    const idx = pesertaCounter++;
    pesertaList.push({ idx, id: pegawai.id, nama: pegawai.nama, nip: pegawai.nip,
      jabatan: pegawai.jabatan, jenjang: pegawai.jenjang,
      sisa_formasi: formasi.sisa, status_formasi: formasi.status,
      formasi_label: formasi.label, formasi_badge: formasi.badge });
    renderPesertaTable();
    rebuildBerkasSection();
    $('#pegawaiSelect').val(null).trigger('change');
  });
});

function renderPesertaTable() {
  const tbody = $('#pesertaBody');
  tbody.empty();
  if (pesertaList.length === 0) {
    tbody.append('<tr id="emptyRow"><td colspan="6" class="text-center text-muted">Belum ada peserta.</td></tr>');
    return;
  }
  pesertaList.forEach((p, i) => {
    tbody.append(`<tr>
      <td class="text-center">${i+1}</td>
      <td>${p.nama}
        <input type="hidden" name="peserta[${p.idx}][pegawai_id]" value="${p.id}">
        <input type="hidden" name="peserta[${p.idx}][sisa_formasi]" value="${p.sisa_formasi ?? ''}">
        <input type="hidden" name="peserta[${p.idx}][status_formasi]" value="${p.status_formasi}">
      </td>
      <td><small>${p.nip}</small></td>
      <td><small>${p.jabatan} / ${p.jenjang}</small></td>
      <td><span class="badge badge-${p.formasi_badge}">${p.formasi_label}</span></td>
      <td class="text-center">
        <button type="button" class="btn btn-danger btn-xs hapus-peserta" data-id="${p.id}"><i class="fas fa-times"></i></button>
      </td>
    </tr>`);
  });
}

$(document).on('click', '.hapus-peserta', function() {
  pesertaList = pesertaList.filter(p => p.id != $(this).data('id'));
  renderPesertaTable(); rebuildBerkasSection();
});

function rebuildBerkasSection() {
  const container = $('#berkasContainer');
  container.empty();
  if (persyaratanList.length === 0 || pesertaList.length === 0) {
    $('#berkasPlaceholder').removeClass('d-none'); container.hide(); return;
  }
  $('#berkasPlaceholder').addClass('d-none'); container.show();
  persyaratanList.forEach(syarat => {
    let html = `<div class="card card-outline card-secondary mb-3">
      <div class="card-header py-2">
        <strong>${syarat.nama_syarat}</strong>
        ${syarat.file_contoh ? `<a href="${syarat.file_contoh}" target="_blank" class="btn btn-xs btn-outline-primary ml-2">Lihat Contoh</a>` : ''}
      </div>
      <div class="card-body p-2">
        <table class="table table-sm table-bordered mb-0">
          <thead class="thead-light"><tr><th>Peserta</th><th>Upload File <small class="text-muted">(maks 2MB)</small></th></tr></thead>
          <tbody>`;
    pesertaList.forEach(p => {
      html += `<tr><td style="width:40%">${p.nama}<br><small class="text-muted">${p.nip}</small></td>
        <td><input type="file" name="berkas[${p.idx}][${syarat.id}]" class="form-control-file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="font-size:0.85rem;"></td></tr>`;
    });
    html += `</tbody></table></div></div>`;
    container.append(html);
  });
}
</script>
@endpush
