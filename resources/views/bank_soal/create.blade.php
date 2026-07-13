@extends('layouts.users.master')

@section('title', 'Tambah Soal — Pusbin JFT')

@section('isi')
<div class="row justify-content-center">
  <div class="col-lg-10">

    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="fas fa-plus mr-2"></i>Tambah Soal Baru</h3>
        <a href="{{ route('bank-soal.index') }}" class="btn btn-default btn-sm">
          <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
      </div>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('bank-soal.store') }}" method="POST" id="formSoal">
      @csrf

      {{-- SECTION 1: Klasifikasi --}}
      <div class="card">
        <div class="card-header bg-light">
          <h3 class="card-title mb-0"><span class="badge badge-primary mr-2">1</span>Klasifikasi Soal</h3>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-bold">Jenis Soal <span class="text-danger">*</span></label>
                <div>
                  <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="jenisMansoskul" name="jenis" value="mansoskul" class="custom-control-input"
                           {{ old('jenis') === 'mansoskul' ? 'checked' : '' }} onchange="toggleJenis()">
                    <label class="custom-control-label" for="jenisMansoskul">
                      <strong>Mansoskul</strong> <small class="text-muted">— manajerial &amp; sosio-kultural, nilai skala 1-5</small>
                    </label>
                  </div>
                  <div class="custom-control custom-radio custom-control-inline mt-1">
                    <input type="radio" id="jenisTeknis" name="jenis" value="teknis" class="custom-control-input"
                           {{ old('jenis', 'teknis') === 'teknis' ? 'checked' : '' }} onchange="toggleJenis()">
                    <label class="custom-control-label" for="jenisTeknis">
                      <strong>Teknis</strong> <small class="text-muted">— sesuai kategori jabatan, jawaban benar/salah</small>
                    </label>
                  </div>
                </div>
              </div>

              {{-- Kategori — khusus Teknis --}}
              <div class="form-group" id="sectionKategori" style="{{ old('jenis', 'teknis') === 'teknis' ? '' : 'display:none;' }}">
                <label class="font-weight-bold">Kategori <span class="text-danger">*</span></label>
                <select name="soal_kategori_id" class="form-control">
                  <option value="">— Pilih Kategori —</option>
                  @foreach ($kategoris as $k)
                  <option value="{{ $k->id }}" {{ old('soal_kategori_id') == $k->id ? 'selected' : '' }}>
                    {{ $k->nama }}
                  </option>
                  @endforeach
                </select>
              </div>

              {{-- Matra — khusus Mansoskul --}}
              <div class="form-group" id="sectionMatra" style="{{ old('jenis') === 'mansoskul' ? '' : 'display:none;' }}">
                <label class="font-weight-bold">Matra <span class="text-danger">*</span></label>
                <select name="matra" class="form-control">
                  <option value="">— Pilih Matra —</option>
                  @foreach (['darat'=>'Darat','laut'=>'Laut','udara'=>'Udara','asdp'=>'ASDP','perkeretaapian'=>'Perkeretaapian'] as $val => $lbl)
                  <option value="{{ $val }}" {{ old('matra') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-bold">Taksonomi Bloom <span class="text-danger">*</span></label>
                <select name="taksonomi_bloom" class="form-control">
                  <option value="">— Pilih —</option>
                  <option value="C1_mengingat"    {{ old('taksonomi_bloom') === 'C1_mengingat'    ? 'selected' : '' }}>C1 — Mengingat (Recall fakta)</option>
                  <option value="C2_memahami"     {{ old('taksonomi_bloom') === 'C2_memahami'     ? 'selected' : '' }}>C2 — Memahami (Jelaskan konsep)</option>
                  <option value="C3_menerapkan"   {{ old('taksonomi_bloom') === 'C3_menerapkan'   ? 'selected' : '' }}>C3 — Menerapkan (Gunakan prosedur)</option>
                  <option value="C4_menganalisis" {{ old('taksonomi_bloom') === 'C4_menganalisis' ? 'selected' : '' }}>C4 — Menganalisis (Urai komponen)</option>
                  <option value="C5_mengevaluasi" {{ old('taksonomi_bloom') === 'C5_mengevaluasi' ? 'selected' : '' }}>C5 — Mengevaluasi (Beri penilaian)</option>
                  <option value="C6_mencipta"     {{ old('taksonomi_bloom') === 'C6_mencipta'     ? 'selected' : '' }}>C6 — Mencipta (Rancang solusi baru)</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- SECTION 2: Soal --}}
      <div class="card">
        <div class="card-header bg-light">
          <h3 class="card-title mb-0"><span class="badge badge-primary mr-2">2</span>Pertanyaan</h3>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="font-weight-bold">Teks Pertanyaan <span class="text-danger">*</span></label>
            <textarea name="pertanyaan" class="form-control" rows="4"
                      placeholder="Tuliskan pertanyaan di sini...">{{ old('pertanyaan') }}</textarea>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold">Pembahasan / Penjelasan Jawaban <small class="text-muted font-weight-normal">(opsional)</small></label>
            <textarea name="pembahasan" class="form-control" rows="3"
                      placeholder="Jelaskan mengapa jawaban tersebut benar...">{{ old('pembahasan') }}</textarea>
          </div>
        </div>
      </div>

      {{-- SECTION 3: Pilihan Jawaban --}}
      <div class="card">
        <div class="card-header bg-light">
          <h3 class="card-title mb-0"><span class="badge badge-primary mr-2">3</span>Pilihan Jawaban</h3>
        </div>
        <div class="card-body">
          {{-- Petunjuk khusus Teknis --}}
          <p class="text-muted small mb-3" id="petunjukTeknis"><i class="fas fa-info-circle mr-1"></i>Centang radio button di sebelah kiri untuk menandai jawaban yang benar.</p>
          {{-- Petunjuk khusus Mansoskul --}}
          <p class="text-muted small mb-3" id="petunjukMansoskul" style="display:none;"><i class="fas fa-info-circle mr-1"></i>Tiap pilihan diberi nilai skala 1-5 (bukan benar/salah) — 5 = paling tepat, 1 = paling tidak tepat.</p>

          @foreach (['A','B','C','D'] as $kode)
          <div class="form-group pilihan-row" id="row{{ $kode }}" style="border:1px solid #dee2e6; border-radius:6px; padding:10px 12px; margin-bottom:10px;">
            <div class="d-flex align-items-center">
              {{-- Radio jawaban benar — khusus Teknis --}}
              <div class="mr-3 pilihan-radio-benar">
                <input type="radio" name="jawaban_benar" value="{{ $kode }}" id="benar{{ $kode }}"
                       {{ old('jawaban_benar') === $kode ? 'checked' : '' }}
                       onchange="highlightJawaban('{{ $kode }}')" style="transform:scale(1.3);">
              </div>
              <div class="mr-3">
                <span class="badge badge-secondary px-2 py-1" style="font-size:0.9rem; min-width:28px;">{{ $kode }}</span>
              </div>
              <div class="flex-fill">
                <input type="text" name="pilihan[{{ $kode }}][teks]" class="form-control form-control-sm"
                       placeholder="Isi pilihan jawaban {{ $kode }}..."
                       value="{{ old('pilihan.'.$kode.'.teks') }}">
              </div>
              {{-- Nilai skala — khusus Mansoskul --}}
              <div class="ml-3 pilihan-nilai-skala" style="display:none; width:120px;">
                <select name="pilihan[{{ $kode }}][nilai_skala]" class="form-control form-control-sm">
                  <option value="">Nilai</option>
                  @for ($n = 1; $n <= 5; $n++)
                  <option value="{{ $n }}" {{ old('pilihan.'.$kode.'.nilai_skala') == $n ? 'selected' : '' }}>Nilai {{ $n }}</option>
                  @endfor
                </select>
              </div>
            </div>
          </div>
          @endforeach
        </div>
      </div>

      {{-- Tombol Simpan --}}
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center">
          <a href="{{ route('bank-soal.index') }}" class="btn btn-default">Batal</a>
          <div>
            <button type="submit" name="simpan_sebagai" value="draft" class="btn btn-secondary mr-2">
              <i class="fas fa-save mr-1"></i> Simpan sebagai Draft
            </button>
            <button type="submit" name="simpan_sebagai" value="aktif" class="btn btn-success">
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
function toggleJenis() {
  const isMansoskul = document.getElementById('jenisMansoskul').checked;

  document.getElementById('sectionKategori').style.display = isMansoskul ? 'none' : '';
  document.getElementById('sectionMatra').style.display    = isMansoskul ? '' : 'none';

  document.getElementById('petunjukTeknis').style.display    = isMansoskul ? 'none' : '';
  document.getElementById('petunjukMansoskul').style.display = isMansoskul ? '' : 'none';

  document.querySelectorAll('.pilihan-radio-benar').forEach(function (el) {
    el.style.display = isMansoskul ? 'none' : '';
  });
  document.querySelectorAll('.pilihan-nilai-skala').forEach(function (el) {
    el.style.display = isMansoskul ? '' : 'none';
  });
}

function highlightJawaban(kode) {
  ['A','B','C','D'].forEach(function(k) {
    const row = document.getElementById('row' + k);
    if (k === kode) {
      row.style.background = '#d4edda';
      row.style.borderColor = '#c3e6cb';
    } else {
      row.style.background = '';
      row.style.borderColor = '#dee2e6';
    }
  });
}

document.addEventListener('DOMContentLoaded', toggleJenis);
</script>
@endpush
