@extends('layouts.users.master')

@section('title', 'Detail Hasil Ujikom — Pusbin JFT')

@push('styles')
<style>
  .chart-bar-container { display:flex; align-items:flex-end; gap:8px; height:80px; }
  .chart-bar-wrap { display:flex; flex-direction:column; align-items:center; flex:1; }
  .chart-bar { background:#3490dc; border-radius:4px 4px 0 0; width:100%; min-height:4px; transition:all .3s; }
  .chart-bar-label { font-size:0.7rem; color:#6c757d; margin-top:3px; }
  .chart-bar-val { font-size:0.72rem; font-weight:600; color:#333; }
</style>
@endpush

@section('isi')
<div class="row">
  <div class="col-12">

    @if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h4 class="mb-1">{{ $jadwal->judul }}</h4>
        <p class="text-muted mb-0 small">
          <i class="far fa-calendar mr-1"></i>{{ $jadwal->tanggal_mulai?->format('d M Y') }}
          @if ($jadwal->tempat) &bull; <i class="fas fa-map-marker-alt mr-1"></i>{{ $jadwal->tempat }} @endif
        </p>
      </div>
      <div class="d-flex gap-1 flex-wrap">
        <a href="{{ route('ujikom.hasil.input-offline', $jadwal->id) }}" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-keyboard mr-1"></i> Input Offline
        </a>
        <a href="{{ route('ujikom.hasil.export-pdf', $jadwal->id) }}" class="btn btn-sm btn-outline-danger" target="_blank">
          <i class="fas fa-file-pdf mr-1"></i> Export PDF
        </a>
        <a href="{{ route('ujikom.hasil.export-excel', $jadwal->id) }}" class="btn btn-sm btn-outline-success">
          <i class="fas fa-file-excel mr-1"></i> Export Excel
        </a>
        <a href="{{ route('ujikom.hasil.index') }}" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
      </div>
    </div>

    {{-- Statistik + Bar Chart --}}
    <div class="row mb-3">
      <div class="col-md-8">
        <div class="row">
          <div class="col-6 col-md-3">
            <div class="info-box shadow-sm mb-2">
              <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
              <div class="info-box-content"><span class="info-box-text">Total</span><span class="info-box-number">{{ $totalPeserta }}</span></div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="info-box shadow-sm mb-2">
              <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Lulus</span>
                <span class="info-box-number">{{ $lulus }}</span>
                @if ($totalPeserta > 0) <span class="info-box-text">{{ round($lulus / $totalPeserta * 100) }}%</span> @endif
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="info-box shadow-sm mb-2">
              <span class="info-box-icon bg-danger"><i class="fas fa-times"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Tidak Lulus</span>
                <span class="info-box-number">{{ $tidakLulus }}</span>
                @if ($totalPeserta > 0) <span class="info-box-text">{{ round($tidakLulus / $totalPeserta * 100) }}%</span> @endif
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="info-box shadow-sm mb-2">
              <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
              <div class="info-box-content"><span class="info-box-text">Belum Dinilai</span><span class="info-box-number">{{ $belumDinilai }}</span></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 mb-2">
          <div class="card-body py-2 px-3">
            <p class="small font-weight-bold mb-2">Distribusi Nilai</p>
            @php $maxBar = max(array_values($distribusiNilai) ?: [1]); @endphp
            <div class="chart-bar-container">
              @foreach ($distribusiNilai as $range => $count)
              @php $height = $maxBar > 0 ? max(4, round($count / $maxBar * 70)) : 4; @endphp
              <div class="chart-bar-wrap">
                <div class="chart-bar-val">{{ $count }}</div>
                <div class="chart-bar" style="height:{{ $height }}px;"></div>
                <div class="chart-bar-label">{{ $range }}</div>
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Tabel Peserta --}}
    <div class="card">
      <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-list mr-2"></i>Daftar Peserta & Hasil</h3></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm mb-0" style="font-size:0.83rem;">
            <thead class="thead-light">
              <tr>
                <th width="40" class="text-center">No</th>
                <th>Nama / NIP</th>
                <th width="140">Unit Kerja</th>
                <th width="80" class="text-center">Jenis</th>
                <th width="70" class="text-center">Nilai</th>
                <th width="90" class="text-center">Status</th>
                <th>Catatan Admin</th>
                <th width="60" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($pesertaList as $i => $p)
              @php $hasil = $hasilMap->get($p->id); @endphp
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>
                  <strong>{{ $p->pegawai?->nama_lengkap ?? '-' }}</strong>
                  <br><small class="text-muted">{{ $p->pegawai?->nip ?? '-' }}</small>
                </td>
                <td><small class="text-muted">{{ $p->pegawai?->unitKerja?->nama_rs ?? '-' }}</small></td>
                <td class="text-center">
                  @if ($hasil)
                    <span class="badge badge-{{ $hasil->jenis_ujian === 'online' ? 'primary' : 'secondary' }}">
                      {{ $hasil->label_jenis }}
                    </span>
                  @else <span class="text-muted">—</span> @endif
                </td>
                <td class="text-center">
                  @if ($hasil && $hasil->nilai !== null)
                    <strong>{{ number_format($hasil->nilai, 0) }}</strong>
                  @else <span class="text-muted">—</span> @endif
                </td>
                <td class="text-center">
                  @if ($hasil)
                    <span class="badge badge-{{ $hasil->badge_status }}">{{ $hasil->label_status }}</span>
                  @else
                    <span class="badge badge-secondary">Belum Dinilai</span>
                  @endif
                </td>
                <td>
                  @if ($hasil)
                  <span class="catatan-text" data-hasil-id="{{ $hasil->id }}">{{ $hasil->catatan_admin ?? '' }}</span>
                  <button class="btn btn-xs btn-link p-0 ml-1 btn-edit-catatan" data-hasil-id="{{ $hasil->id }}" title="Edit catatan">
                    <i class="fas fa-pencil-alt text-muted" style="font-size:0.75rem;"></i>
                  </button>
                  <div class="catatan-form d-none" data-hasil-id="{{ $hasil->id }}">
                    <div class="input-group input-group-sm mt-1">
                      <input type="text" class="form-control form-control-sm catatan-input"
                             value="{{ $hasil->catatan_admin ?? '' }}" placeholder="Catatan...">
                      <div class="input-group-append">
                        <button class="btn btn-success btn-sm btn-save-catatan" data-hasil-id="{{ $hasil->id }}">
                          <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-default btn-sm btn-cancel-catatan" data-hasil-id="{{ $hasil->id }}">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  @else <span class="text-muted">—</span> @endif
                </td>
                <td class="text-center">
                  @if (!$hasil || $hasil->status_kelulusan === 'belum_dinilai')
                  <a href="{{ route('ujikom.hasil.input-offline', $jadwal->id) }}" class="btn btn-xs btn-outline-warning" title="Input nilai">
                    <i class="fas fa-edit"></i>
                  </a>
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="8" class="text-center text-muted py-4">Belum ada peserta terdaftar.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
// Inline edit catatan
$(document).on('click', '.btn-edit-catatan', function() {
    const id = $(this).data('hasil-id');
    $(`.catatan-text[data-hasil-id="${id}"]`).addClass('d-none');
    $(this).addClass('d-none');
    $(`.catatan-form[data-hasil-id="${id}"]`).removeClass('d-none');
});

$(document).on('click', '.btn-cancel-catatan', function() {
    const id = $(this).data('hasil-id');
    $(`.catatan-form[data-hasil-id="${id}"]`).addClass('d-none');
    $(`.catatan-text[data-hasil-id="${id}"]`).removeClass('d-none');
    $(`.btn-edit-catatan[data-hasil-id="${id}"]`).removeClass('d-none');
});

$(document).on('click', '.btn-save-catatan', function() {
    const id    = $(this).data('hasil-id');
    const nilai = $(`.catatan-form[data-hasil-id="${id}"] .catatan-input`).val();

    $.ajax({
        url: `/ujikom/hasil/catatan/${id}`,
        method: 'POST',
        data: { _token: '{{ csrf_token() }}', catatan_admin: nilai },
        success: function() {
            $(`.catatan-text[data-hasil-id="${id}"]`).text(nilai).removeClass('d-none');
            $(`.catatan-form[data-hasil-id="${id}"]`).addClass('d-none');
            $(`.btn-edit-catatan[data-hasil-id="${id}"]`).removeClass('d-none');
        },
        error: function() {
            alert('Gagal menyimpan catatan.');
        }
    });
});
</script>
@endpush
