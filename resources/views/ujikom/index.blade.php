@extends('layouts.users.master')
@section('title', 'Permohonan Uji Kompetensi')
@section('isi')

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Permohonan Uji Kompetensi</h4>
    @can('create ujikom')
      <a href="{{ route('ujikom.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Buat Permohonan
      </a>
    @endcan
  </div>

  {{-- Filter Card --}}
  <div class="card filter-card mb-3">
    <div class="card-header">
      <h5 class="mb-0"><i class="fas fa-filter"></i> Filter</h5>
    </div>
    <div class="card-body">
      <form method="GET" action="{{ route('ujikom.index') }}" class="filter-row">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">Semua Status</option>
              <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
              <option value="diajukan" {{ request('status') == 'diajukan' ? 'selected' : '' }}>Diajukan</option>
              <option value="diverifikasi" {{ request('status') == 'diverifikasi' ? 'selected' : '' }}>Diverifikasi</option>
              <option value="terjadwal" {{ request('status') == 'terjadwal' ? 'selected' : '' }}>Terjadwal</option>
              <option value="selesai_uji" {{ request('status') == 'selesai_uji' ? 'selected' : '' }}>Selesai Uji</option>
              <option value="hasil_diinput" {{ request('status') == 'hasil_diinput' ? 'selected' : '' }}>Hasil Diinput</option>
              <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Unit Kerja</label>
            <select name="unit_kerja_id" class="form-select">
              <option value="">Semua Unit Kerja</option>
              @foreach($unitKerja as $uk)
                <option value="{{ $uk->no_rs }}" {{ request('unit_kerja_id') == $uk->no_rs ? 'selected' : '' }}>
                  {{ $uk->nama_rumahsakit }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Tahun</label>
            <select name="tahun" class="form-select">
              <option value="">Semua Tahun</option>
              @foreach($tahuns as $tahun)
                <option value="{{ $tahun }}" {{ request('tahun') == $tahun ? 'selected' : '' }}>
                  {{ $tahun }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-search"></i> Filter
            </button>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <a href="{{ route('ujikom.index') }}" class="btn btn-secondary w-100">
              <i class="fas fa-redo"></i> Reset
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- Tabel Data --}}
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table id="ujikomTable" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th width="50">No</th>
              <th>Nomor Permohonan</th>
              <th>Unit Kerja</th>
              <th>Tanggal</th>
              <th>Jml Peserta</th>
              <th>Status</th>
              <th width="150">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($permohonan as $i => $row)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row->nomor_permohonan }}</td>
                <td>{{ $row->unitKerja->nama_rumahsakit ?? '-' }}</td>
                <td>{{ $row->tanggal_permohonan->format('d/m/Y') }}</td>
                <td>{{ $row->peserta->count() }}</td>
                <td>
                  <span class="badge bg-{{ $row->status_badge_color }}">
                    {{ $row->status_label }}
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('ujikom.show', $row->id) }}"
                       class="btn btn-info"
                       title="Detail">
                      <i class="fas fa-eye"></i>
                    </a>
                    @if($row->bisaDiedit())
                      @can('edit ujikom')
                        <a href="{{ route('ujikom.edit', $row->id) }}"
                           class="btn btn-warning"
                           title="Edit">
                          <i class="fas fa-edit"></i>
                        </a>
                      @endcan
                    @endif
                    @if($row->bisaDihapus())
                      @can('delete ujikom')
                        <button type="button"
                                class="btn btn-danger"
                                onclick="confirmDelete({{ $row->id }})"
                                title="Hapus">
                          <i class="fas fa-trash"></i>
                        </button>
                      @endcan
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
$(function() {
  $('#ujikomTable').DataTable({
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [[2, 'desc']],
    autoWidth: false,
  });
});

function confirmDelete(id) {
  swal({
    title: 'Apakah Anda yakin?',
    text: 'Permohonan akan dihapus!',
    icon: 'warning',
    buttons: true,
    dangerMode: true,
  })
  .then((willDelete) => {
    if (willDelete) {
      $.ajax({
        url: '{{ route('ujikom.index') }}/' + id,
        type: 'DELETE',
        data: {
          _token: '{{ csrf_token() }}'
        },
        success: function(response) {
          swal('Sukses!', 'Permohonan berhasil dihapus.', 'success')
            .then(() => {
              location.reload();
            });
        },
        error: function(xhr) {
          var error = xhr.responseJSON?.error || 'Terjadi kesalahan.';
          swal('Error!', error, 'error');
        }
      });
    }
  });
}
</script>
@endpush
@endsection
