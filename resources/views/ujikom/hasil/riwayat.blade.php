@extends('layouts.users.master')

@section('title', 'Riwayat Uji Kompetensi Saya — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0"><i class="fas fa-history mr-2"></i>Riwayat Uji Kompetensi Saya</h3>
      </div>
      <div class="card-body p-0">
        @if ($riwayat->isEmpty())
          <div class="text-center text-muted py-5">
            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
            <p class="mb-1">Belum ada riwayat uji kompetensi.</p>
            <small>Riwayat akan muncul setelah Anda mengikuti dan menyelesaikan ujian.</small>
          </div>
        @else
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm mb-0" style="font-size:0.85rem;">
            <thead class="thead-light">
              <tr>
                <th width="40" class="text-center">No</th>
                <th>Nama Jadwal</th>
                <th width="120" class="text-center">Tanggal Ujian</th>
                <th width="80" class="text-center">Jenis</th>
                <th width="70" class="text-center">Nilai</th>
                <th width="90" class="text-center">Passing Grade</th>
                <th width="110" class="text-center">Status Kelulusan</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($riwayat as $i => $r)
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td><strong>{{ $r->jadwal?->judul ?? '-' }}</strong></td>
                <td class="text-center"><small>{{ $r->tanggal_ujian?->format('d M Y') ?? '-' }}</small></td>
                <td class="text-center">
                  <span class="badge badge-{{ $r->jenis_ujian === 'online' ? 'primary' : 'secondary' }}">
                    {{ $r->label_jenis }}
                  </span>
                </td>
                <td class="text-center">
                  @if ($r->nilai !== null)
                    <strong class="text-{{ $r->status_kelulusan === 'lulus' ? 'success' : ($r->status_kelulusan === 'tidak_lulus' ? 'danger' : 'muted') }}">
                      {{ number_format($r->nilai, 0) }}
                    </strong>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-center">
                  @if ($r->passing_grade !== null)
                    <span class="badge badge-secondary">{{ $r->passing_grade }}%</span>
                  @else <span class="text-muted">—</span> @endif
                </td>
                <td class="text-center">
                  <span class="badge badge-{{ $r->badge_status }}">{{ $r->label_status }}</span>
                </td>
                <td><small class="text-muted">{{ $r->catatan_admin ?? '—' }}</small></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif
      </div>
    </div>

  </div>
</div>
@endsection
