@extends('layouts.users.master')

@section('title', 'Input Nilai — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    @if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0"><i class="fas fa-star-half-alt mr-2"></i>Jadwal Menunggu Nilai Wawancara/Presentasi</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm mb-0" style="font-size:0.85rem;">
            <thead class="thead-light">
              <tr>
                <th width="40" class="text-center">No</th>
                <th>Jadwal Ujikom</th>
                <th width="110" class="text-center">Tanggal</th>
                <th width="90" class="text-center">Peserta</th>
                <th width="120" class="text-center">Slot Kosong</th>
                <th width="140" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($pending as $i => $row)
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>
                  <strong>{{ $row['jadwal']->judul }}</strong>
                  @if ($row['jadwal']->jenis_ujian)
                  <br><small class="text-muted">{{ $row['jadwal']->jenis_ujian_label }}</small>
                  @endif
                </td>
                <td class="text-center"><small>{{ $row['jadwal']->tanggal_mulai?->format('d M Y') }}</small></td>
                <td class="text-center">{{ $row['jumlah_peserta'] }}</td>
                <td class="text-center">
                  <span class="badge badge-warning">{{ $row['slot_kosong'] }} dari {{ $row['slot_dibutuhkan'] }}</span>
                </td>
                <td class="text-center">
                  <a href="{{ route('ujikom-online.admin.nilai-manual.form', $row['jadwal']->id) }}" class="btn btn-xs btn-primary">
                    <i class="fas fa-star-half-alt mr-1"></i> Input Nilai
                  </a>
                </td>
              </tr>
              @empty
              <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada jadwal yang menunggu nilai Wawancara/Presentasi saat ini.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection
