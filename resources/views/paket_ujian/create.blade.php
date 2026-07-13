@extends('layouts.users.master')

@section('title', 'Buat Paket Ujian — Pusbin JFT')

@section('isi')
<div class="row justify-content-center">
  <div class="col-lg-11">

    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="fas fa-plus mr-2"></i>Buat Paket Ujian Baru</h3>
        <a href="{{ route('paket-ujian.index') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
      </div>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('paket-ujian.store') }}" method="POST" id="formPaket">
      @csrf

      {{-- SECTION 1: Info Paket --}}
      <div class="card">
        <div class="card-header bg-light">
          <h3 class="card-title mb-0"><span class="badge badge-primary mr-2">1</span>Informasi Paket</h3>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label class="font-weight-bold">Nama Paket <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control" value="{{ old('nama') }}"
                       placeholder="contoh: Paket Ujian PKB Terampil Gelombang I 2026">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="font-weight-bold">Jadwal Ujikom <small class="text-muted font-weight-normal">(opsional)</small></label>
                <select name="ujikom_jadwal_id" class="form-control">
                  <option value="">— Standalone / Tidak terikat jadwal —</option>
                  @foreach ($jadwals as $j)
                  <option value="{{ $j->id }}" {{ old('ujikom_jadwal_id') == $j->id ? 'selected' : '' }}>{{ $j->judul }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-12">
              <div class="form-group">
                <label class="font-weight-bold">Deskripsi <small class="text-muted font-weight-normal">(opsional)</small></label>
                <textarea name="deskripsi" class="form-control" rows="2">{{ old('deskripsi') }}</textarea>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="font-weight-bold">Durasi <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="number" name="durasi_menit" class="form-control" value="{{ old('durasi_menit', 90) }}" min="5" max="360">
                  <div class="input-group-append"><span class="input-group-text">menit</span></div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="font-weight-bold">Jumlah Soal <span class="text-danger">*</span></label>
                <input type="number" name="jumlah_soal" class="form-control" value="{{ old('jumlah_soal', 40) }}" min="1" id="inputJumlahSoal">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="font-weight-bold">Passing Grade <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="number" name="passing_grade" class="form-control" value="{{ old('passing_grade', 70) }}" min="0" max="100" id="inputPG">
                  <div class="input-group-append"><span class="input-group-text">%</span></div>
                </div>
                <input type="range" min="0" max="100" value="{{ old('passing_grade', 70) }}" class="custom-range mt-1" id="sliderPG"
                       oninput="document.getElementById('inputPG').value=this.value">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="font-weight-bold d-block">Pengacakan</label>
                <div class="custom-control custom-switch mb-1">
                  <input type="hidden" name="acak_soal" value="0">
                  <input type="checkbox" class="custom-control-input" id="acakSoal" name="acak_soal" value="1" {{ old('acak_soal', '1') ? 'checked' : '' }}>
                  <label class="custom-control-label" for="acakSoal">Acak Urutan Soal</label>
                </div>
                <div class="custom-control custom-switch">
                  <input type="hidden" name="acak_pilihan" value="0">
                  <input type="checkbox" class="custom-control-input" id="acakPilihan" name="acak_pilihan" value="1" {{ old('acak_pilihan', '1') ? 'checked' : '' }}>
                  <label class="custom-control-label" for="acakPilihan">Acak Pilihan Jawaban</label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- SECTION 2: Mode Pemilihan Soal --}}
      <div class="card">
        <div class="card-header bg-light">
          <h3 class="card-title mb-0"><span class="badge badge-primary mr-2">2</span>Mode Pemilihan Soal</h3>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-4">
              <div class="custom-control custom-radio">
                <input type="radio" id="modeAcak" name="mode_pemilihan" value="acak_otomatis" class="custom-control-input"
                       {{ old('mode_pemilihan', 'acak_otomatis') === 'acak_otomatis' ? 'checked' : '' }} onchange="toggleMode()">
                <label class="custom-control-label font-weight-bold" for="modeAcak">
                  <i class="fas fa-random mr-1 text-primary"></i> Acak Otomatis
                  <small class="d-block text-muted font-weight-normal">Sistem memilih soal secara acak dari bank soal sesuai konfigurasi kategori</small>
                </label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="custom-control custom-radio">
                <input type="radio" id="modeManual" name="mode_pemilihan" value="manual" class="custom-control-input"
                       {{ old('mode_pemilihan') === 'manual' ? 'checked' : '' }} onchange="toggleMode()">
                <label class="custom-control-label font-weight-bold" for="modeManual">
                  <i class="fas fa-hand-pointer mr-1 text-info"></i> Manual
                  <small class="d-block text-muted font-weight-normal">Admin memilih soal satu per satu dari daftar bank soal</small>
                </label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="custom-control custom-radio">
                <input type="radio" id="modeSesi" name="mode_pemilihan" value="sesi_taksonomi" class="custom-control-input"
                       {{ old('mode_pemilihan') === 'sesi_taksonomi' ? 'checked' : '' }} onchange="toggleMode()">
                <label class="custom-control-label font-weight-bold" for="modeSesi">
                  <i class="fas fa-layer-group mr-1 text-success"></i> 2 Sesi CAT
                  <small class="d-block text-muted font-weight-normal">Sesi Teknis + Mansoskul terpisah, komposisi taksonomi dihitung otomatis</small>
                </label>
              </div>
            </div>
          </div>

          {{-- Panel: Acak Otomatis --}}
          <div id="panelAcak" style="{{ old('mode_pemilihan') === 'manual' ? 'display:none;' : '' }}">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="font-weight-bold mb-0">Konfigurasi Kategori Soal</h6>
              <div>
                <span class="mr-3 text-muted small">Total dikonfigurasi: <strong id="totalAcak">0</strong> soal</span>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="tambahBarisAcak()">
                  <i class="fas fa-plus mr-1"></i> Tambah Kategori
                </button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-sm" id="tabelAcak">
                <thead class="thead-light">
                  <tr>
                    <th width="120">Jenis Soal</th>
                    <th>Kategori</th>
                    <th width="120">Jumlah Soal</th>
                    <th width="80" class="text-center">Hapus</th>
                  </tr>
                </thead>
                <tbody id="bodyAcak">
                  {{-- Baris default --}}
                  <tr>
                    <td>
                      <select name="kategori_acak[0][jenis_soal]" class="form-control form-control-sm jenis-acak" onchange="toggleKategoriAcak(this)">
                        <option value="umum">Umum</option>
                        <option value="spesifik">Spesifik</option>
                      </select>
                    </td>
                    <td>
                      <select name="kategori_acak[0][soal_kategori_id]" class="form-control form-control-sm select-kategori-acak" disabled>
                        <option value="">— Pilih Kategori —</option>
                        @foreach ($kategoris as $k)
                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                        @endforeach
                      </select>
                    </td>
                    <td>
                      <input type="number" name="kategori_acak[0][jumlah_soal]" class="form-control form-control-sm jumlah-acak" value="10" min="1" onchange="hitungTotal()">
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-xs btn-outline-danger" onclick="hapusBaris(this)"><i class="fas fa-times"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div id="warningKuota" class="alert alert-warning py-2 mt-2" style="display:none; font-size:0.85rem;">
              <i class="fas fa-exclamation-triangle mr-1"></i>
              <span id="pesanKuota"></span>
            </div>
          </div>

          {{-- Panel: Manual --}}
          <div id="panelManual" style="{{ old('mode_pemilihan') !== 'manual' ? 'display:none;' : '' }}">
            <div class="row mb-2">
              <div class="col-md-3">
                <select id="filterKategori" class="form-control form-control-sm" onchange="filterSoal()">
                  <option value="">— Semua Kategori —</option>
                  @foreach ($kategoris as $k)
                  <option value="{{ $k->id }}">{{ $k->nama }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-2">
                <select id="filterJenis" class="form-control form-control-sm" onchange="filterSoal()">
                  <option value="">— Semua Jenis —</option>
                  <option value="mansoskul">Mansoskul</option>
                  <option value="teknis">Teknis</option>
                </select>
              </div>
              <div class="col-md-4">
                <input type="text" id="filterCari" class="form-control form-control-sm" placeholder="Cari pertanyaan..." oninput="filterSoal()">
              </div>
              <div class="col-md-3">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-block" onclick="resetFilter()">Reset Filter</button>
              </div>
            </div>

            <div class="row">
              {{-- Daftar Soal Tersedia --}}
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <h6 class="font-weight-bold mb-0 small">SOAL TERSEDIA</h6>
                  <small class="text-muted" id="infoBankSoal">Memuat...</small>
                </div>
                <div style="height:320px; overflow-y:auto; border:1px solid #dee2e6; border-radius:4px;" id="listTersedia">
                  <div class="p-3 text-muted text-center small">Gunakan filter di atas untuk memuat soal...</div>
                </div>
              </div>
              {{-- Soal Terpilih --}}
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <h6 class="font-weight-bold mb-0 small">SOAL TERPILIH</h6>
                  <small class="text-muted"><strong id="totalTerpilih">0</strong> soal dipilih</small>
                </div>
                <div style="height:320px; overflow-y:auto; border:1px solid #28a745; border-radius:4px; background:#f8fff9;" id="listTerpilih">
                  <div class="p-3 text-muted text-center small" id="emptyTerpilih">Belum ada soal dipilih. Klik soal di kiri untuk memilih.</div>
                </div>
                <div id="hiddenSoalTerpilih"></div>
              </div>
            </div>
          </div>

          {{-- Panel: 2 Sesi CAT --}}
          <div id="panelSesiCat" style="{{ old('mode_pemilihan') === 'sesi_taksonomi' ? '' : 'display:none;' }}">
            <p class="text-muted small mb-3"><i class="fas fa-info-circle mr-1"></i>Isi minimal satu sesi. Komposisi jumlah soal per taksonomi dihitung otomatis berbobot berjenjang (taksonomi lebih tinggi mendapat porsi lebih besar).</p>
            <div class="row">
              {{-- Sesi Teknis --}}
              <div class="col-md-6">
                <div class="card border-primary mb-2">
                  <div class="card-header bg-light py-2"><strong><i class="fas fa-toolbox mr-1 text-primary"></i>Sesi Teknis</strong></div>
                  <div class="card-body">
                    <div class="form-group">
                      <label class="small font-weight-bold mb-1">Kategori Soal</label>
                      <select name="soal_kategori_id_teknis" id="selKategoriTeknis" class="form-control form-control-sm" onchange="previewKomposisi('teknis')">
                        <option value="">— Pilih Kategori —</option>
                        @foreach ($kategoris as $k)
                        <option value="{{ $k->id }}" {{ old('soal_kategori_id_teknis') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="form-group">
                      <label class="small font-weight-bold mb-1">Taksonomi Maksimal</label>
                      <select name="taksonomi_maks_teknis" id="selTaksonomiTeknis" class="form-control form-control-sm" onchange="previewKomposisi('teknis')">
                        <option value="">— Pilih —</option>
                        @foreach (['C1_mengingat'=>'C1 — Mengingat','C2_memahami'=>'C2 — Memahami','C3_menerapkan'=>'C3 — Menerapkan','C4_menganalisis'=>'C4 — Menganalisis','C5_mengevaluasi'=>'C5 — Mengevaluasi','C6_mencipta'=>'C6 — Mencipta'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('taksonomi_maks_teknis') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="row">
                      <div class="col-6">
                        <div class="form-group mb-2">
                          <label class="small font-weight-bold mb-1">Jumlah Soal</label>
                          <input type="number" name="jumlah_soal_teknis" id="inputJumlahTeknis" class="form-control form-control-sm"
                                 value="{{ old('jumlah_soal_teknis') }}" min="1" oninput="previewKomposisi('teknis')">
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="form-group mb-2">
                          <label class="small font-weight-bold mb-1">Durasi (menit)</label>
                          <input type="number" name="durasi_menit_teknis" class="form-control form-control-sm"
                                 value="{{ old('durasi_menit_teknis', 60) }}" min="5" max="360">
                        </div>
                      </div>
                    </div>
                    <div id="previewTeknis"></div>
                  </div>
                </div>
              </div>
              {{-- Sesi Mansoskul --}}
              <div class="col-md-6">
                <div class="card border-success mb-2">
                  <div class="card-header bg-light py-2"><strong><i class="fas fa-users mr-1 text-success"></i>Sesi Mansoskul</strong></div>
                  <div class="card-body">
                    <div class="form-group">
                      <label class="small font-weight-bold mb-1">Matra</label>
                      <select name="matra_mansoskul" id="selMatraMansoskul" class="form-control form-control-sm" onchange="previewKomposisi('mansoskul')">
                        <option value="">— Pilih Matra —</option>
                        @foreach (['darat'=>'Darat','laut'=>'Laut','udara'=>'Udara','asdp'=>'ASDP','perkeretaapian'=>'Perkeretaapian'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('matra_mansoskul') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="form-group">
                      <label class="small font-weight-bold mb-1">Taksonomi Maksimal</label>
                      <select name="taksonomi_maks_mansoskul" id="selTaksonomiMansoskul" class="form-control form-control-sm" onchange="previewKomposisi('mansoskul')">
                        <option value="">— Pilih —</option>
                        @foreach (['C1_mengingat'=>'C1 — Mengingat','C2_memahami'=>'C2 — Memahami','C3_menerapkan'=>'C3 — Menerapkan','C4_menganalisis'=>'C4 — Menganalisis','C5_mengevaluasi'=>'C5 — Mengevaluasi','C6_mencipta'=>'C6 — Mencipta'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('taksonomi_maks_mansoskul') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="row">
                      <div class="col-6">
                        <div class="form-group mb-2">
                          <label class="small font-weight-bold mb-1">Jumlah Soal</label>
                          <input type="number" name="jumlah_soal_mansoskul" id="inputJumlahMansoskul" class="form-control form-control-sm"
                                 value="{{ old('jumlah_soal_mansoskul') }}" min="1" oninput="previewKomposisi('mansoskul')">
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="form-group mb-2">
                          <label class="small font-weight-bold mb-1">Durasi (menit)</label>
                          <input type="number" name="durasi_menit_mansoskul" class="form-control form-control-sm"
                                 value="{{ old('durasi_menit_mansoskul', 60) }}" min="5" max="360">
                        </div>
                      </div>
                    </div>
                    <div id="previewMansoskul"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Tombol Simpan --}}
      <div class="card">
        <div class="card-body d-flex justify-content-between">
          <a href="{{ route('paket-ujian.index') }}" class="btn btn-default">Batal</a>
          <div>
            <button type="submit" name="simpan_sebagai" value="draft" class="btn btn-secondary mr-2">
              <i class="fas fa-save mr-1"></i> Simpan sebagai Draft
            </button>
            <button type="submit" name="simpan_sebagai" value="aktif" class="btn btn-success" id="btnAktifkan">
              <i class="fas fa-check-circle mr-1"></i> Simpan & Aktifkan
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
let barisAcakIdx = 1;
const kategoriOptions = @json($kategoris->map(fn($k) => ['id' => $k->id, 'nama' => $k->nama]));
let soalBank = [];
let soalTerpilih = [];

// ── Toggle mode panel ──
function toggleMode() {
  const mode = document.querySelector('input[name="mode_pemilihan"]:checked').value;
  document.getElementById('panelAcak').style.display    = mode === 'acak_otomatis'   ? '' : 'none';
  document.getElementById('panelManual').style.display  = mode === 'manual'          ? '' : 'none';
  document.getElementById('panelSesiCat').style.display = mode === 'sesi_taksonomi'  ? '' : 'none';
}

// ── Preview Komposisi Taksonomi (2 Sesi CAT) ──
function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

function previewKomposisi(jenisSesi) {
  const suffix        = capitalize(jenisSesi);
  const taksonomiMaks = document.getElementById('selTaksonomi' + suffix).value;
  const jumlahSoal    = document.getElementById('inputJumlah' + suffix).value;
  const container     = document.getElementById('preview' + suffix);

  if (!taksonomiMaks || !jumlahSoal || parseInt(jumlahSoal) < 1) {
    container.innerHTML = '';
    return;
  }

  const params = new URLSearchParams({ jenis_sesi: jenisSesi, taksonomi_maks: taksonomiMaks, jumlah_soal: jumlahSoal });
  if (jenisSesi === 'teknis') {
    params.append('soal_kategori_id', document.getElementById('selKategoriTeknis').value);
  } else {
    params.append('matra', document.getElementById('selMatraMansoskul').value);
  }

  container.innerHTML = '<div class="text-muted small mt-2"><i class="fas fa-spinner fa-spin mr-1"></i>Menghitung komposisi...</div>';

  fetch('{{ route('paket-ujian.preview-komposisi') }}?' + params.toString())
    .then(r => r.json())
    .then(data => {
      let html = '<div class="table-responsive mt-2"><table class="table table-sm table-bordered mb-0" style="font-size:0.78rem;">' +
        '<thead class="thead-light"><tr><th>Taksonomi</th><th class="text-center" width="60">Butuh</th><th class="text-center" width="70">Tersedia</th></tr></thead><tbody>';
      data.detail.forEach(function (d) {
        html += `<tr class="${d.cukup ? '' : 'table-danger'}"><td>${d.label}</td><td class="text-center">${d.jumlah}</td><td class="text-center">${d.tersedia}</td></tr>`;
      });
      html += '</tbody></table></div>';
      if (!data.cukup) {
        html += '<div class="text-danger small mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>Sebagian taksonomi kekurangan soal di bank soal.</div>';
      }
      container.innerHTML = html;
    })
    .catch(function () {
      container.innerHTML = '<div class="text-danger small mt-2">Gagal memuat preview komposisi.</div>';
    });
}

// ── Acak Otomatis ──
function tambahBarisAcak() {
  const idx = barisAcakIdx++;
  const opts = kategoriOptions.map(k => `<option value="${k.id}">${k.nama}</option>`).join('');
  const html = `<tr>
    <td>
      <select name="kategori_acak[${idx}][jenis_soal]" class="form-control form-control-sm jenis-acak" onchange="toggleKategoriAcak(this)">
        <option value="umum">Umum</option>
        <option value="spesifik">Spesifik</option>
      </select>
    </td>
    <td>
      <select name="kategori_acak[${idx}][soal_kategori_id]" class="form-control form-control-sm select-kategori-acak" disabled>
        <option value="">— Pilih Kategori —</option>${opts}
      </select>
    </td>
    <td>
      <input type="number" name="kategori_acak[${idx}][jumlah_soal]" class="form-control form-control-sm jumlah-acak" value="10" min="1" onchange="hitungTotal()">
    </td>
    <td class="text-center">
      <button type="button" class="btn btn-xs btn-outline-danger" onclick="hapusBaris(this)"><i class="fas fa-times"></i></button>
    </td>
  </tr>`;
  document.getElementById('bodyAcak').insertAdjacentHTML('beforeend', html);
  hitungTotal();
}

function hapusBaris(btn) {
  btn.closest('tr').remove();
  hitungTotal();
}

function toggleKategoriAcak(sel) {
  const isSpesifik = sel.value === 'spesifik';
  const katSel = sel.closest('tr').querySelector('.select-kategori-acak');
  katSel.disabled = !isSpesifik;
  if (!isSpesifik) katSel.value = '';
}

function hitungTotal() {
  let total = 0;
  document.querySelectorAll('.jumlah-acak').forEach(inp => { total += parseInt(inp.value) || 0; });
  document.getElementById('totalAcak').textContent = total;

  const target = parseInt(document.getElementById('inputJumlahSoal').value) || 0;
  const warn = document.getElementById('warningKuota');
  if (target > 0 && total !== target) {
    document.getElementById('pesanKuota').textContent = `Total soal dikonfigurasi (${total}) tidak sesuai jumlah soal paket (${target}).`;
    warn.style.display = '';
  } else {
    warn.style.display = 'none';
  }
}

// ── Mode Manual ──
function filterSoal() {
  const kategoriId = document.getElementById('filterKategori').value;
  const jenis      = document.getElementById('filterJenis').value;
  const q          = document.getElementById('filterCari').value;

  const params = new URLSearchParams();
  if (kategoriId) params.append('kategori_id', kategoriId);
  if (jenis)      params.append('jenis', jenis);
  if (q)          params.append('q', q);

  document.getElementById('listTersedia').innerHTML = '<div class="p-3 text-center text-muted small"><i class="fas fa-spinner fa-spin mr-1"></i>Memuat...</div>';

  fetch('/paket-ujian/get-soal-kategori?' + params.toString())
    .then(r => r.json())
    .then(data => {
      soalBank = data;
      renderTersedia();
      document.getElementById('infoBankSoal').textContent = data.length + ' soal ditemukan';
    });
}

function resetFilter() {
  document.getElementById('filterKategori').value = '';
  document.getElementById('filterJenis').value    = '';
  document.getElementById('filterCari').value     = '';
  filterSoal();
}

function renderTersedia() {
  const container = document.getElementById('listTersedia');
  const dipilihIds = soalTerpilih.map(s => s.id);
  const tampil = soalBank.filter(s => !dipilihIds.includes(s.id));

  if (tampil.length === 0) {
    container.innerHTML = '<div class="p-3 text-muted text-center small">Tidak ada soal tersedia.</div>';
    return;
  }

  container.innerHTML = tampil.map(s => `
    <div class="d-flex align-items-center px-2 py-1 border-bottom soal-item" style="cursor:pointer;font-size:0.8rem;" onclick="pilihSoal(${s.id})" title="${s.pertanyaan}">
      <span class="badge badge-${s.badge} mr-2" style="min-width:60px;font-size:0.7rem;">${s.label_jenis}</span>
      <span class="badge badge-secondary mr-2" style="font-size:0.65rem;">${s.taksonomi}</span>
      <span class="flex-fill text-truncate">${s.pertanyaan}</span>
      <i class="fas fa-plus text-success ml-2"></i>
    </div>`).join('');
}

function pilihSoal(id) {
  const soal = soalBank.find(s => s.id === id);
  if (!soal || soalTerpilih.find(s => s.id === id)) return;
  soalTerpilih.push(soal);
  renderTerpilih();
  renderTersedia();
}

function hapusTerpilih(id) {
  soalTerpilih = soalTerpilih.filter(s => s.id !== id);
  renderTerpilih();
  renderTersedia();
}

function renderTerpilih() {
  const container = document.getElementById('listTerpilih');
  const hidden    = document.getElementById('hiddenSoalTerpilih');
  document.getElementById('totalTerpilih').textContent = soalTerpilih.length;

  if (soalTerpilih.length === 0) {
    container.innerHTML = '<div class="p-3 text-muted text-center small" id="emptyTerpilih">Belum ada soal dipilih.</div>';
    hidden.innerHTML = '';
    return;
  }

  container.innerHTML = soalTerpilih.map((s, i) => `
    <div class="d-flex align-items-center px-2 py-1 border-bottom" style="font-size:0.8rem;">
      <span class="text-muted mr-2" style="min-width:20px;">${i+1}.</span>
      <span class="badge badge-${s.badge} mr-2" style="min-width:60px;font-size:0.7rem;">${s.label_jenis}</span>
      <span class="flex-fill text-truncate">${s.pertanyaan}</span>
      <button type="button" class="btn btn-xs btn-outline-danger ml-2" onclick="hapusTerpilih(${s.id})"><i class="fas fa-times"></i></button>
    </div>`).join('');

  hidden.innerHTML = soalTerpilih.map((s, i) =>
    `<input type="hidden" name="soal_terpilih[${i}]" value="${s.id}">`
  ).join('');
}

// Init
document.getElementById('sliderPG').addEventListener('input', function() {
  document.getElementById('inputPG').value = this.value;
});
document.getElementById('inputPG').addEventListener('input', function() {
  document.getElementById('sliderPG').value = this.value;
});
hitungTotal();
</script>
@endpush
