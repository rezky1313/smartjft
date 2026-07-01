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
                    <input type="radio" id="jenisUmum" name="jenis" value="umum" class="custom-control-input"
                           {{ old('jenis', 'umum') === 'umum' ? 'checked' : '' }} onchange="toggleKategori()">
                    <label class="custom-control-label" for="jenisUmum">
                      <strong>Umum</strong> <small class="text-muted">— berlaku untuk semua peserta</small>
                    </label>
                  </div>
                  <div class="custom-control custom-radio custom-control-inline mt-1">
                    <input type="radio" id="jenisSpesifik" name="jenis" value="spesifik" class="custom-control-input"
                           {{ old('jenis') === 'spesifik' ? 'checked' : '' }} onchange="toggleKategori()">
                    <label class="custom-control-label" for="jenisSpesifik">
                      <strong>Spesifik</strong> <small class="text-muted">— sesuai kategori jabatan</small>
                    </label>
                  </div>
                </div>
              </div>
              <div class="form-group" id="sectionKategori" style="{{ old('jenis') === 'spesifik' ? '' : 'display:none;' }}">
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
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="font-weight-bold">Tingkat Kesulitan <span class="text-danger">*</span></label>
                <select name="tingkat_kesulitan" class="form-control">
                  <option value="">— Pilih —</option>
                  <option value="mudah"  {{ old('tingkat_kesulitan') === 'mudah'  ? 'selected' : '' }}>Mudah</option>
                  <option value="sedang" {{ old('tingkat_kesulitan') === 'sedang' ? 'selected' : '' }}>Sedang</option>
                  <option value="sulit"  {{ old('tingkat_kesulitan') === 'sulit'  ? 'selected' : '' }}>Sulit</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
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
          <p class="text-muted small mb-3"><i class="fas fa-info-circle mr-1"></i>Centang radio button di sebelah kiri untuk menandai jawaban yang benar.</p>
          @foreach (['A','B','C','D'] as $kode)
          <div class="form-group pilihan-row {{ old('jawaban_benar') === $kode ? 'bg-light-green' : '' }}" id="row{{ $kode }}" style="border:1px solid #dee2e6; border-radius:6px; padding:10px 12px; margin-bottom:10px; {{ old('jawaban_benar') === $kode ? 'background:#d4edda!important;border-color:#c3e6cb!important;' : '' }}">
            <div class="d-flex align-items-center">
              <div class="mr-3">
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
function toggleKategori() {
  const isSpesifik = document.getElementById('jenisSpesifik').checked;
  document.getElementById('sectionKategori').style.display = isSpesifik ? '' : 'none';
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
</script>
@endpush
