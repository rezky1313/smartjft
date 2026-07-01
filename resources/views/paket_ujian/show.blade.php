@extends('layouts.users.master')

@section('title', 'Detail Paket Ujian — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    {{-- Header + Aksi --}}
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h4 class="mb-1">{{ $paket->nama }}</h4>
        <span class="badge badge-{{ $paket->badge_status }} mr-1">{{ $paket->label_status }}</span>
        <span class="badge badge-{{ $paket->mode_pemilihan === 'manual' ? 'info' : 'primary' }}">{{ $paket->label_mode }}</span>
      </div>
      <div class="d-flex gap-1 flex-wrap">
        @if ($paket->status === 'draft')
          <a href="{{ route('paket-ujian.edit', $paket->id) }}" class="btn btn-sm btn-outline-warning">
            <i class="fas fa-pencil-alt mr-1"></i> Edit
          </a>
          <form action="{{ route('paket-ujian.aktifkan', $paket->id) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Aktifkan paket ini?')">
            @csrf
            <button class="btn btn-sm btn-success"><i class="fas fa-check mr-1"></i> Aktifkan</button>
          </form>
          <form action="{{ route('paket-ujian.destroy', $paket->id) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Hapus paket ini? Data tidak dapat dikembalikan.')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash mr-1"></i> Hapus</button>
          </form>
        @elseif ($paket->status === 'aktif')
          <form action="{{ route('paket-ujian.nonaktifkan', $paket->id) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Nonaktifkan paket ini?')">
            @csrf
            <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-ban mr-1"></i> Nonaktifkan</button>
          </form>
        @endif
        <a href="{{ route('paket-ujian.index') }}" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
      </div>
    </div>

    {{-- Alert --}}
    @if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    {{-- Warning ketersediaan soal --}}
    @if (!$ketersediaan['cukup'])
    <div class="alert alert-warning d-flex align-items-start">
      <i class="fas fa-exclamation-triangle mt-1 mr-2"></i>
      <div>
        <strong>Soal di bank tidak mencukupi!</strong> Paket ini tidak dapat diaktifkan karena:
        <ul class="mb-0 mt-1">
          @if ($paket->mode_pemilihan === 'manual')
            @foreach ($ketersediaan['detail'] as $d)
            <li>{{ $d }}</li>
            @endforeach
          @else
            @foreach ($ketersediaan['detail'] as $d)
              @if (!$d['cukup'])
              <li>{{ $d['label'] }}: butuh <strong>{{ $d['butuh'] }}</strong>, tersedia <strong>{{ $d['tersedia'] }}</strong></li>
              @endif
            @endforeach
          @endif
        </ul>
      </div>
    </div>
    @endif

    <div class="row">

      {{-- Kolom kiri: Info Paket + Konfigurasi --}}
      <div class="col-lg-4">

        {{-- Info Paket --}}
        <div class="card card-outline card-primary">
          <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Info Paket</h3></div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0" style="font-size:0.85rem;">
              <tr>
                <td class="text-muted pl-3" width="130">Jadwal Ujikom</td>
                <td>{{ $paket->jadwal?->judul ?? '<span class="text-muted">— Standalone —</span>' }}</td>
              </tr>
              <tr>
                <td class="text-muted pl-3">Durasi</td>
                <td><strong>{{ $paket->durasi_menit }}</strong> menit</td>
              </tr>
              <tr>
                <td class="text-muted pl-3">Jumlah Soal</td>
                <td><strong>{{ $paket->jumlah_soal }}</strong> soal</td>
              </tr>
              <tr>
                <td class="text-muted pl-3">Passing Grade</td>
                <td><span class="badge badge-secondary">{{ $paket->passing_grade }}%</span></td>
              </tr>
              <tr>
                <td class="text-muted pl-3">Acak Soal</td>
                <td>
                  @if ($paket->acak_soal)
                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Ya</span>
                  @else
                    <span class="badge badge-secondary">Tidak</span>
                  @endif
                </td>
              </tr>
              <tr>
                <td class="text-muted pl-3">Acak Pilihan</td>
                <td>
                  @if ($paket->acak_pilihan)
                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Ya</span>
                  @else
                    <span class="badge badge-secondary">Tidak</span>
                  @endif
                </td>
              </tr>
              <tr>
                <td class="text-muted pl-3">Dibuat Oleh</td>
                <td>{{ $paket->pembuat?->name ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted pl-3">Dibuat</td>
                <td>{{ $paket->created_at->format('d M Y H:i') }}</td>
              </tr>
            </table>
          </div>
        </div>

        @if ($paket->deskripsi)
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="fas fa-align-left mr-1"></i> Deskripsi</h3></div>
          <div class="card-body" style="font-size:0.85rem;">{{ $paket->deskripsi }}</div>
        </div>
        @endif

        {{-- Konfigurasi Mode Acak --}}
        @if ($paket->mode_pemilihan === 'acak_otomatis')
        <div class="card card-outline card-primary">
          <div class="card-header"><h3 class="card-title"><i class="fas fa-random mr-1"></i> Konfigurasi Acak</h3></div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0" style="font-size:0.84rem;">
              <thead class="thead-light">
                <tr>
                  <th class="pl-3">Kategori Soal</th>
                  <th class="text-center" width="70">Butuh</th>
                  <th class="text-center" width="80">Tersedia</th>
                  <th class="text-center" width="60">OK?</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($ketersediaan['detail'] as $d)
                <tr>
                  <td class="pl-3">{{ $d['label'] }}</td>
                  <td class="text-center">{{ $d['butuh'] }}</td>
                  <td class="text-center">{{ $d['tersedia'] }}</td>
                  <td class="text-center">
                    @if ($d['cukup'])
                      <i class="fas fa-check text-success"></i>
                    @else
                      <i class="fas fa-times text-danger"></i>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
              <tfoot class="thead-light">
                <tr>
                  <td class="pl-3 font-weight-bold">Total</td>
                  <td class="text-center font-weight-bold">{{ $paket->jumlah_soal }}</td>
                  <td colspan="2"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        {{-- Preview Soal Acak --}}
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="fas fa-eye mr-1"></i> Preview Set Soal</h3></div>
          <div class="card-body">
            <p class="text-muted small mb-2">Lihat contoh set soal yang akan diterima peserta (digenerate secara acak).</p>
            <button class="btn btn-sm btn-outline-primary btn-block" id="btnPreview">
              <i class="fas fa-dice mr-1"></i> Generate Preview
            </button>
            <div id="previewResult" class="mt-2" style="display:none;"></div>
          </div>
        </div>
        @endif

        {{-- Statistik Tingkat Kesulitan (mode manual) --}}
        @if ($paket->mode_pemilihan === 'manual' && count($statTingkat))
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Statistik Soal</h3></div>
          <div class="card-body pb-2">
            @php
              $tingkatMap = ['mudah' => ['label' => 'Mudah', 'color' => 'success'], 'sedang' => ['label' => 'Sedang', 'color' => 'warning'], 'sulit' => ['label' => 'Sulit', 'color' => 'danger']];
              $total = array_sum($statTingkat);
            @endphp
            @foreach ($tingkatMap as $key => $meta)
              @php $jml = $statTingkat[$key] ?? 0; $pct = $total > 0 ? round($jml / $total * 100) : 0; @endphp
              <div class="mb-2">
                <div class="d-flex justify-content-between mb-1" style="font-size:0.82rem;">
                  <span>{{ $meta['label'] }}</span><span>{{ $jml }} soal ({{ $pct }}%)</span>
                </div>
                <div class="progress" style="height:8px;">
                  <div class="progress-bar bg-{{ $meta['color'] }}" style="width:{{ $pct }}%"></div>
                </div>
              </div>
            @endforeach
            <p class="text-muted mb-0 mt-2" style="font-size:0.8rem;">Total: {{ $total }} soal terpilih dari {{ $paket->jumlah_soal }} target</p>
          </div>
        </div>
        @endif

      </div>

      {{-- Kolom kanan: Daftar Soal --}}
      <div class="col-lg-8">

        @if ($paket->mode_pemilihan === 'manual')
        <div class="card">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0"><i class="fas fa-list-ol mr-1"></i> Daftar Soal Terpilih</h3>
            <span class="badge badge-primary">{{ $paket->bankSoal->count() }} soal</span>
          </div>
          <div class="card-body p-0">
            @if ($paket->bankSoal->isEmpty())
            <div class="text-center text-muted py-4">
              <i class="fas fa-inbox fa-2x mb-2 d-block"></i> Belum ada soal yang dipilih.
            </div>
            @else
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0" style="font-size:0.83rem;">
                <thead class="thead-light">
                  <tr>
                    <th width="40" class="text-center">No</th>
                    <th>Pertanyaan</th>
                    <th width="130">Kategori</th>
                    <th width="80" class="text-center">Tingkat</th>
                    <th width="90" class="text-center">Kunci</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($paket->bankSoal as $i => $soal)
                  <tr>
                    <td class="text-center text-muted">{{ $i + 1 }}</td>
                    <td>
                      {{ Str::limit(strip_tags($soal->pertanyaan), 120) }}
                      @if ($soal->jenis === 'essay')
                      <span class="badge badge-light border ml-1" style="font-size:0.7rem;">Essay</span>
                      @endif
                    </td>
                    <td><small class="text-muted">{{ $soal->kategori?->nama ?? '— Umum —' }}</small></td>
                    <td class="text-center">
                      <span class="badge badge-{{ $soal->badge_tingkat }}">{{ $soal->label_tingkat }}</span>
                    </td>
                    <td class="text-center">
                      @php $jawaban = $soal->jawabanBenar->first(); @endphp
                      @if ($jawaban)
                        <span class="badge badge-outline-success border-success text-success font-weight-bold">
                          {{ strtoupper($jawaban->kode_pilihan) }}
                        </span>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            @endif
          </div>
        </div>

        @else
        {{-- Mode Acak: tampilkan ringkasan konfigurasi + preview hasil --}}
        <div class="card">
          <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-random mr-1"></i> Konfigurasi Pemilihan Soal Acak</h3>
          </div>
          <div class="card-body">
            <p class="text-muted small mb-3">
              Pada mode acak otomatis, set soal berbeda akan digenerate untuk setiap peserta saat ujian dimulai.
              Berikut adalah konfigurasi pengambilan soal:
            </p>
            <div class="row">
              @foreach ($paket->kategoriAcak as $cfg)
              @php
                $detail = collect($ketersediaan['detail'])->firstWhere('label', $cfg->jenis_soal === 'umum' ? 'Soal Umum' : ($cfg->kategori?->nama ?? "Kategori #{$cfg->soal_kategori_id}"));
              @endphp
              <div class="col-md-6 mb-3">
                <div class="card border mb-0 {{ ($detail && !$detail['cukup']) ? 'border-danger' : 'border-success' }}">
                  <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between">
                      <div>
                        <h6 class="mb-1" style="font-size:0.9rem;">
                          @if ($cfg->jenis_soal === 'umum')
                            <i class="fas fa-globe mr-1 text-muted"></i> Soal Umum
                          @else
                            <i class="fas fa-tag mr-1 text-muted"></i> {{ $cfg->kategori?->nama ?? 'Kategori tidak diketahui' }}
                          @endif
                        </h6>
                        <small class="text-muted">Ambil <strong>{{ $cfg->jumlah_soal }}</strong> soal secara acak</small>
                      </div>
                      @if ($detail)
                        @if ($detail['cukup'])
                          <span class="badge badge-success ml-2 mt-1"><i class="fas fa-check"></i></span>
                        @else
                          <span class="badge badge-danger ml-2 mt-1"><i class="fas fa-times"></i></span>
                        @endif
                      @endif
                    </div>
                    @if ($detail)
                    <div class="mt-2 pt-2 border-top">
                      <small class="text-muted">
                        Tersedia di bank: <strong>{{ $detail['tersedia'] }}</strong> soal aktif
                      </small>
                    </div>
                    @endif
                  </div>
                </div>
              </div>
              @endforeach
            </div>
          </div>
        </div>

        {{-- Area hasil preview --}}
        <div class="card" id="previewCard" style="display:none;">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0"><i class="fas fa-eye mr-1"></i> Hasil Preview Soal</h3>
            <button class="btn btn-sm btn-outline-secondary" onclick="$('#previewCard').hide()">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="card-body p-0" id="previewTableContainer"></div>
        </div>
        @endif

      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
@if ($paket->mode_pemilihan === 'acak_otomatis')
$('#btnPreview').on('click', function () {
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menggenerate...');

    $.get('{{ route('paket-ujian.preview', $paket->id) }}')
      .done(function (res) {
        // Update sidebar mini-result
        let miniHtml = `<div class="alert alert-info py-2 mb-2" style="font-size:0.8rem;">
          <i class="fas fa-check-circle mr-1"></i> <strong>${res.jumlah}</strong> soal berhasil digenerate.
        </div>`;
        $('#previewResult').html(miniHtml).show();

        // Build full table in right panel
        let tableHtml = `<div class="table-responsive">
          <table class="table table-sm table-hover mb-0" style="font-size:0.83rem;">
            <thead class="thead-light">
              <tr>
                <th class="text-center" width="40">No</th>
                <th>Pertanyaan</th>
                <th width="130">Kategori</th>
                <th class="text-center" width="90">Tingkat</th>
                <th class="text-center" width="90">Taksonomi</th>
              </tr>
            </thead>
            <tbody>`;
        res.soal.forEach(function (s, i) {
            tableHtml += `<tr>
              <td class="text-center text-muted">${i+1}</td>
              <td>${s.pertanyaan}</td>
              <td><small class="text-muted">${s.kategori}</small></td>
              <td class="text-center"><span class="badge badge-${s.badge_tingkat}">${s.tingkat}</span></td>
              <td class="text-center"><small class="text-muted">${s.taksonomi}</small></td>
            </tr>`;
        });
        tableHtml += `</tbody></table></div>`;
        $('#previewTableContainer').html(tableHtml);
        $('#previewCard').show();
        $('html, body').animate({ scrollTop: $('#previewCard').offset().top - 80 }, 400);
      })
      .fail(function () {
        $('#previewResult').html('<div class="alert alert-danger py-2" style="font-size:0.8rem;">Gagal memuat preview. Pastikan bank soal memiliki cukup soal aktif.</div>').show();
      })
      .always(function () {
        btn.prop('disabled', false).html('<i class="fas fa-dice mr-1"></i> Generate Ulang');
      });
});
@endif
</script>
@endpush
