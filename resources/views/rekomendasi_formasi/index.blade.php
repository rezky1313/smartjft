@extends('layouts.users.master')
@section('title', 'Rekomendasi Formasi PKB')

@section('isi')

@if (session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="preview-card mb-4">
  <div class="preview-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
      <span class="preview-header-title">Rekomendasi Formasi</span>
      <span class="preview-header-subtitle d-block">Usulan penambahan formasi JF Penguji Kendaraan Bermotor (PKB)</span>
    </div>
    @can('create rekomendasi formasi')
    <a href="{{ route('user.rekomendasi-formasi.create') }}" class="btn btn-primary btn-sm">+ Buat Usulan</a>
    @endcan
  </div>

  <div class="preview-body">
    <form method="get" class="d-flex flex-wrap align-items-end mb-3">
      <div class="mr-2 mb-2">
        <label class="subsection-label mb-1">Tahun</label>
        <select name="tahun" class="form-control form-control-sm" style="width:120px;">
          <option value="">Semua Tahun</option>
          @foreach($tahunList as $t)
            <option value="{{ $t }}" {{ request('tahun') == $t ? 'selected' : '' }}>{{ $t }}</option>
          @endforeach
        </select>
      </div>
      <div class="mr-2 mb-2">
        <label class="subsection-label mb-1">Status</label>
        <select name="status" class="form-control form-control-sm" style="width:200px;">
          <option value="">Semua Status</option>
          @foreach(['draft'=>'Draft','diajukan'=>'Diajukan','menunggu_verifikasi'=>'Menunggu Verifikasi','verifikasi_disepakati'=>'Verifikasi Disepakati','menunggu_ttd_ba'=>'Menunggu TTD BA','ba_selesai'=>'BA Selesai','menunggu_ttd_rekomendasi'=>'Menunggu TTD Rekomendasi','selesai'=>'Selesai','ditolak'=>'Ditolak'] as $val => $label)
            <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="mb-2">
        <button type="submit" class="btn btn-primary btn-sm mr-1">Terapkan</button>
        <a href="{{ route('user.rekomendasi-formasi.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="thead-dark">
          <tr>
            <th>No</th>
            <th>Unit Kerja</th>
            <th>Jenis Instansi</th>
            <th>Tahun</th>
            <th>Diajukan Oleh</th>
            <th>Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($usulanList as $i => $usulan)
            <tr>
              <td>{{ $usulanList->firstItem() + $i }}</td>
              <td>{{ $usulan->unitKerja->nama_unit_kerja ?? '-' }}</td>
              <td>{{ $usulan->jenis_instansi ? ucfirst($usulan->jenis_instansi) : '-' }}</td>
              <td>{{ $usulan->tahun }}</td>
              <td>{{ $usulan->pengaju->name ?? '-' }}</td>
              <td><span class="do-badge" style="background:#e0e7ff; color:#3730a3;">{{ $usulan->label_status }}</span></td>
              <td class="text-center">
                <a href="{{ route('user.rekomendasi-formasi.show', $usulan->id) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                  <i class="fas fa-eye"></i>
                </a>
                @if(in_array($usulan->status, ['draft','diajukan']))
                  <a href="{{ route('user.rekomendasi-formasi.edit', $usulan->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted">Belum ada usulan rekomendasi formasi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $usulanList->links() }}
    </div>
  </div>
</div>
@endsection
