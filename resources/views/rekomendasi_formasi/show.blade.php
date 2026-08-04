@extends('layouts.users.master')
@section('title', 'Detail Usulan Rekomendasi Formasi')

@section('isi')

@if (session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="preview-card mb-4">
  <div class="preview-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
      <span class="preview-header-title">Usulan Rekomendasi Formasi PKB</span>
      <span class="preview-header-subtitle d-block">{{ $usulan->unitKerja->nama_unit_kerja ?? '-' }} &middot; Tahun {{ $usulan->tahun }}</span>
    </div>
    <div>
      <span class="do-badge" style="background:#e0e7ff; color:#3730a3;">{{ $usulan->label_status }}</span>
      @if(in_array($usulan->status, ['draft','diajukan']))
        <a href="{{ route('user.rekomendasi-formasi.edit', $usulan->id) }}" class="btn btn-sm btn-outline-primary ml-2">Edit</a>
      @endif
    </div>
  </div>

  <div class="preview-body">

    {{-- Timeline / Stepper Status (Bagian 6) --}}
    <div class="stepper stepper-horizontal mb-4">
      @foreach($stepLabels as $index => $label)
        @php
          $isCompleted = $index < $currentStatusIndex;
          $isActive = $index == $currentStatusIndex;
        @endphp
        <div class="step @if($isCompleted) completed @elseif($isActive) active @else pending @endif">
          <div class="step-circle">
            @if($isCompleted)
              <i class="fas fa-check-circle"></i>
            @elseif($isActive)
              <i class="fas fa-circle"></i>
            @else
              <i class="far fa-circle"></i>
            @endif
          </div>
          <div class="step-title">{{ $label }}</div>
        </div>
      @endforeach
    </div>
    <style>
      .stepper-horizontal { display: flex; justify-content: space-between; position: relative; padding: 20px 0; }
      .stepper-horizontal::before { content:''; position:absolute; top:40px; left:50px; right:50px; height:2px; background:#e9ecef; z-index:0; }
      .step { flex:1; text-align:center; position:relative; z-index:1; }
      .step-circle { width:40px; height:40px; border-radius:50%; background:#fff; border:2px solid #dee2e6; display:flex; align-items:center; justify-content:center; margin:0 auto 10px; font-size:16px; }
      .step.completed .step-circle { border-color:#28a745; color:#28a745; }
      .step.active .step-circle { border-color:#007bff; background:#007bff; color:#fff; }
      .step.pending .step-circle { border-color:#dee2e6; color:#6c757d; }
      .step-title { font-weight:600; font-size:11px; }
      .step.completed .step-title { color:#28a745; }
      .step.active .step-title { color:#007bff; }
      .step.pending .step-title { color:#6c757d; }
    </style>

    @if($usulan->catatan_override)
    <div class="alert alert-warning" style="font-size:12px; white-space:pre-line;">
      <strong><i class="fas fa-history mr-1"></i>Riwayat Override:</strong><br>
      {{ $usulan->catatan_override }}
    </div>
    @endif

    <div class="row mb-4">
      <div class="col-md-3">
        <span class="subsection-label d-block">Jenis Instansi</span>
        {{ ucfirst($usulan->jenis_instansi) }}
      </div>
      <div class="col-md-3">
        <span class="subsection-label d-block">Diajukan Oleh</span>
        {{ $usulan->pengaju->name ?? '-' }}
      </div>
      <div class="col-md-3">
        <span class="subsection-label d-block">Tanggal Dibuat</span>
        {{ $usulan->created_at->format('d-m-Y H:i') }}
      </div>
      <div class="col-md-3">
        <span class="subsection-label d-block">Status</span>
        {{ $usulan->label_status }}
      </div>
    </div>

    {{-- Aksi: Verifikasi & Override --}}
    @hasanyrole('admin|super_admin|kabid_perencanaan_jft')
    @if(in_array($usulan->status, ['diajukan', 'menunggu_verifikasi']))
    <div class="mb-4">
      <button type="button" class="btn btn-success" onclick="konfirmasiVerifikasiDisepakati()">
        <i class="fas fa-handshake mr-1"></i> Tandai Verifikasi Telah Disepakati
      </button>
    </div>
    <form id="form-verifikasi-disepakati" method="post" action="{{ route('user.rekomendasi-formasi.verifikasi-disepakati', $usulan->id) }}" class="d-none">
      @csrf
    </form>
    @endif
    @endhasanyrole

    @hasanyrole('admin|super_admin')
    @if(!in_array($usulan->status, ['draft', 'selesai']))
    <div class="mb-4">
      <button type="button" class="btn btn-outline-warning btn-sm" onclick="konfirmasiKembalikanDraft()">
        <i class="fas fa-undo mr-1"></i> Kembalikan ke Draft (Kasus Khusus)
      </button>
    </div>
    <form id="form-kembalikan-draft" method="post" action="{{ route('user.rekomendasi-formasi.kembalikan-draft', $usulan->id) }}" class="d-none">
      @csrf
      <input type="hidden" name="alasan" id="input-alasan-kembalikan">
    </form>
    @endif
    @endhasanyrole

    @if($usulan->variabel)
    <h6 class="font-weight-bold mb-2">Data Beban Kerja</h6>
    <div class="table-responsive mb-4">
      <table class="table table-sm table-bordered">
        <tbody>
          <tr>
            <td class="font-weight-bold" style="width:220px;">Jumlah KBWU</td>
            <td>{{ number_format($usulan->variabel->jumlah_kbwu) }}</td>
            <td class="font-weight-bold" style="width:220px;">KB Diuji Total (otomatis)</td>
            <td>{{ number_format($usulan->variabel->kb_diuji_total) }}</td>
          </tr>
          <tr>
            <td class="font-weight-bold">Uji Pertama</td>
            <td>{{ number_format($usulan->variabel->uji_pertama) }}</td>
            <td class="font-weight-bold">Uji Reguler</td>
            <td>{{ number_format($usulan->variabel->uji_reguler) }}</td>
          </tr>
          <tr>
            <td class="font-weight-bold">Numpang Uji Masuk / Keluar</td>
            <td>{{ number_format($usulan->variabel->numpang_uji_masuk) }} / {{ number_format($usulan->variabel->numpang_uji_keluar) }}</td>
            <td class="font-weight-bold">Mutasi Masuk / Keluar</td>
            <td>{{ number_format($usulan->variabel->mutasi_masuk) }} / {{ number_format($usulan->variabel->mutasi_keluar) }}</td>
          </tr>
          <tr>
            <td class="font-weight-bold">BBM Bensin / Solar</td>
            <td>{{ number_format($usulan->variabel->bbm_bensin) }} / {{ number_format($usulan->variabel->bbm_solar) }}</td>
            <td class="font-weight-bold">Hari Kerja</td>
            <td>{{ $usulan->variabel->hari_kerja }} hari</td>
          </tr>
        </tbody>
      </table>
    </div>
    @endif

    <h6 class="font-weight-bold mb-2">Hasil Kalkulasi Kebutuhan Formasi</h6>

    <div class="alert alert-light border mb-3" style="font-size:12px;">
      <i class="fas fa-info-circle mr-1"></i>
      Sistem membulatkan Kebutuhan ke atas (ROUNDUP) untuk semua jenjang. Kolom <strong>Kebutuhan (Raw)</strong> menampilkan angka sebelum dibulatkan
      @hasanyrole('admin|super_admin|kabid_perencanaan_jft')
      — kalau pimpinan menghendaki pembulatan ke bawah (ROUNDDOWN) untuk jenjang tertentu, gunakan kolom <strong>Formasi Final</strong> di bawah untuk override manual (bisa diedit sampai Berita Acara ditandatangani kedua pihak).
      @else
      .
      @endhasanyrole
    </div>

    <form method="post" action="{{ route('user.rekomendasi-formasi.hasil-final.update', $usulan->id) }}">
      @csrf
      @method('PUT')
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead class="thead-dark">
            <tr>
              <th>Jenjang</th>
              <th>Kebutuhan (Raw)</th>
              <th>Kebutuhan (Dibulatkan)</th>
              <th>Bezetting</th>
              <th>Formasi (Sistem)</th>
              <th>Formasi Final</th>
              <th>Usulan Admin Unit</th>
              <th>Selisih</th>
            </tr>
          </thead>
          <tbody>
            @php $bisaEditFinal = !in_array($usulan->status, ['ba_selesai','menunggu_ttd_rekomendasi','selesai']); @endphp
            @foreach($usulan->hasil->sortBy(fn($h) => array_search($h->jenjang, ['pemula','terampil','mahir','penyelia'])) as $h)
            <tr>
              <td>{{ ucfirst($h->jenjang) }}</td>
              <td class="text-muted">{{ number_format((float) $h->kebutuhan_raw, 4) }}</td>
              <td>{{ $h->kebutuhan_bulat }}</td>
              <td>{{ $h->bezetting }}</td>
              <td>{{ $h->formasi_sistem }}</td>
              <td class="font-weight-bold">
                @hasanyrole('admin|super_admin|kabid_perencanaan_jft')
                  @if($bisaEditFinal)
                    <input type="number" min="0" name="formasi_final[{{ $h->jenjang }}]" class="form-control form-control-sm" style="width:80px;" value="{{ $h->formasi_final }}">
                  @else
                    {{ $h->formasi_final }}
                  @endif
                  @if($h->formasi_final != $h->formasi_sistem)
                    <small class="text-warning d-block">Di-override manual</small>
                  @endif
                @else
                  {{ $h->formasi_final }}
                @endhasanyrole
              </td>
              <td>{{ $h->usulan_admin_unit ?? '-' }}</td>
              <td>
                @if($h->usulan_admin_unit !== null && $h->usulan_admin_unit != $h->formasi_final)
                  <span class="do-badge" style="background:#fee2e2;color:#991b1b;">Berbeda {{ abs($h->usulan_admin_unit - $h->formasi_final) }}</span>
                @else
                  <span class="do-badge" style="background:#d1fae5;color:#065f46;">Sesuai</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      @hasanyrole('admin|super_admin|kabid_perencanaan_jft')
      @if($bisaEditFinal)
      <button type="submit" class="btn btn-sm btn-primary mb-3">Simpan Formasi Final</button>
      @endif
      @endhasanyrole
    </form>

    @if($usulan->jenis_instansi === 'dishub' && $usulan->pegawaiExisting->isNotEmpty())
    <h6 class="font-weight-bold mb-2 mt-4">Data Pegawai Existing yang Diupload</h6>
    <div class="table-responsive mb-4">
      <table class="table table-sm table-bordered">
        <thead class="thead-dark">
          <tr><th>Nama</th><th>NIP</th><th>Jenjang</th></tr>
        </thead>
        <tbody>
          @foreach($usulan->pegawaiExisting as $p)
          <tr>
            <td>{{ $p->nama }}</td>
            <td>{{ $p->nip ?? '-' }}</td>
            <td>{{ ucfirst($p->jenjang) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif

    {{-- Berita Acara & Tanda Tangan Digital (Bagian 3-4) --}}
    @if($usulan->beritaAcara)
    <hr>
    <h6 class="font-weight-bold mb-2">Berita Acara Hasil Verifikasi</h6>
    <p class="mb-2">
      Nomor: <strong>{{ $usulan->beritaAcara->nomor_ba }}</strong> &middot;
      Tanggal Verifikasi: {{ $usulan->beritaAcara->tanggal_verifikasi?->format('d F Y') }}
      &middot;
      <a href="{{ route('user.rekomendasi-formasi.berita-acara', $usulan->id) }}" target="_blank">
        <i class="fas fa-file-pdf mr-1"></i>Download PDF Berita Acara
      </a>
    </p>

    <div class="d-flex justify-content-between mt-3 flex-wrap">
      <div class="text-center mb-3" style="width:45%;">
        <p class="font-weight-bold">Kepala Bidang Perencanaan & Pembentukan JFT</p>
        @if($usulan->beritaAcara->ttd_pusbin_oleh)
          <div class="do-badge" style="background:#d1fae5;color:#065f46;">
            &#10003; Ditandatangani oleh {{ $usulan->beritaAcara->ttdPusbinOleh->name }}<br>
            <small>{{ $usulan->beritaAcara->ttd_pusbin_at->format('d M Y H:i') }}</small>
          </div>
        @elseif(auth()->user()->hasRole('kabid_perencanaan_jft'))
          <button type="button" class="btn btn-primary btn-sm" onclick="konfirmasiTtdBa()">Setuju & Tandatangani Digital</button>
        @else
          <span class="text-muted">Menunggu tanda tangan</span>
        @endif
      </div>
      <div class="text-center mb-3" style="width:45%;">
        <p class="font-weight-bold">Kepala Kantor/Dishub Pengusul</p>
        @if($usulan->beritaAcara->ttd_pengusul_oleh)
          <div class="do-badge" style="background:#d1fae5;color:#065f46;">
            &#10003; Ditandatangani oleh {{ $usulan->beritaAcara->ttdPengusulOleh->name }}<br>
            <small>{{ $usulan->beritaAcara->ttd_pengusul_at->format('d M Y H:i') }}</small>
          </div>
        @elseif(auth()->user()->hasRole('admin_unit') && auth()->user()->unit_kerja_id == $usulan->unit_kerja_id)
          <button type="button" class="btn btn-primary btn-sm" onclick="konfirmasiTtdBa()">Setuju & Tandatangani Digital</button>
        @else
          <span class="text-muted">Menunggu tanda tangan</span>
        @endif
      </div>
    </div>
    <form id="form-tanda-tangan-ba" method="post" action="{{ route('user.rekomendasi-formasi.tanda-tangan-ba', $usulan->id) }}" class="d-none">
      @csrf
    </form>
    @endif

    {{-- Surat Rekomendasi Formasi (Bagian 5) --}}
    @if(in_array($usulan->status, ['ba_selesai', 'menunggu_ttd_rekomendasi', 'selesai']))
    <hr>
    <h6 class="font-weight-bold mb-2">Surat Rekomendasi Formasi</h6>
    <div class="mb-2">
      @hasanyrole('admin|super_admin')
      <a href="{{ route('user.rekomendasi-formasi.surat-rekomendasi', $usulan->id) }}" class="btn btn-success" target="_blank">
        <i class="fas fa-file-signature mr-1"></i>
        {{ $usulan->status === 'ba_selesai' ? 'Terbitkan Surat Rekomendasi' : 'Download Surat Rekomendasi' }}
      </a>
      @if($usulan->status === 'menunggu_ttd_rekomendasi')
        <form action="{{ route('user.rekomendasi-formasi.konfirmasi-ttd-rekomendasi', $usulan->id) }}" method="POST" class="d-inline"
              onsubmit="return confirm('Konfirmasi surat sudah ditandatangani? Kuota Formasi akan diperbarui.')">
          @csrf
          <button class="btn btn-success"><i class="fas fa-signature mr-1"></i>Konfirmasi Surat Sudah Ditandatangani</button>
        </form>
      @endif
      @endhasanyrole
      @if($usulan->status === 'selesai')
        <span class="do-badge" style="background:#d1fae5;color:#065f46;">Selesai — Kuota Formasi telah diperbarui</span>
      @endif
    </div>
    @endif

  </div>
</div>

<a href="{{ route('user.rekomendasi-formasi.index') }}" class="btn btn-outline-secondary">&larr; Kembali ke Daftar</a>
@endsection

@push('scripts')
<script>
function konfirmasiVerifikasiDisepakati() {
  Swal.fire({
    title: 'Verifikasi Telah Disepakati?',
    text: 'Pastikan pertemuan verifikasi dengan unit pengusul sudah benar-benar dilakukan dan disepakati sebelum menekan tombol ini.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Tandai Disepakati',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#28a745',
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById('form-verifikasi-disepakati').submit();
    }
  });
}

function konfirmasiTtdBa() {
  Swal.fire({
    title: 'Tandatangani Berita Acara?',
    text: 'Dengan menandatangani, Anda menyatakan telah menyetujui hasil verifikasi Berita Acara ini. Lanjutkan?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Tandatangani',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#007bff',
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById('form-tanda-tangan-ba').submit();
    }
  });
}

function konfirmasiKembalikanDraft() {
  Swal.fire({
    title: 'Kembalikan ke Draft?',
    text: 'Data usulan yang sudah diverifikasi akan bisa diedit ulang. Wajib isi alasan.',
    icon: 'warning',
    input: 'textarea',
    inputPlaceholder: 'Tuliskan alasan mengembalikan usulan ini ke Draft...',
    inputValidator: (value) => {
      if (!value || value.trim().length < 5) {
        return 'Alasan wajib diisi (minimal 5 karakter).';
      }
    },
    showCancelButton: true,
    confirmButtonText: 'Ya, Kembalikan ke Draft',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#ffc107',
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById('input-alasan-kembalikan').value = result.value;
      document.getElementById('form-kembalikan-draft').submit();
    }
  });
}
</script>
@endpush
