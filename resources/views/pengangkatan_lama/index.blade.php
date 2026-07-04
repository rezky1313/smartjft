@extends('layouts.users.master')

@section('title', 'Pertimbangan Pengangkatan JFT')

@section('isi')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Pertimbangan Pengangkatan JFT</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('user.peta') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Pertimbangan Pengangkatan</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @can('create pengangkatan')
        <div class="row mb-3">
            <div class="col-12">
                <a href="{{ route('pengangkatan.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Buat Permohonan
                </a>
            </div>
        </div>
        @endcan

        {{-- Filter Card --}}
        <div class="card filter-card">
            <div class="card-header">
                <h3 class="card-title">Filter</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('pengangkatan.index') }}" class="filter-row">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Jalur</label>
                            <select name="jalur" class="form-select select2">
                                <option value="">Semua Jalur</option>
                                @foreach($jalurs as $key => $label)
                                    <option value="{{ $key }}" {{ request('jalur') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select select2">
                                <option value="">Semua Status</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="diajukan" {{ request('status') == 'diajukan' ? 'selected' : '' }}>Diajukan</option>
                                <option value="diverifikasi" {{ request('status') == 'diverifikasi' ? 'selected' : '' }}>Diverifikasi</option>
                                <option value="draft_surat" {{ request('status') == 'draft_surat' ? 'selected' : '' }}>Draft Surat</option>
                                <option value="paraf_katim" {{ request('status') == 'paraf_katim' ? 'selected' : '' }}>Paraf Katim</option>
                                <option value="paraf_kabid" {{ request('status') == 'paraf_kabid' ? 'selected' : '' }}>Paraf Kabid</option>
                                <option value="tanda_tangan" {{ request('status') == 'tanda_tangan' ? 'selected' : '' }}>Tanda Tangan</option>
                                <option value="penomoran" {{ request('status') == 'penomoran' ? 'selected' : '' }}>Penomoran</option>
                                <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit Kerja</label>
                            <select name="unit_kerja_id" class="form-select select2">
                                <option value="">Semua Unit Kerja</option>
                                @foreach($unitKerja as $uk)
                                    <option value="{{ $uk->no_rs }}" {{ request('unit_kerja_id') == $uk->no_rs ? 'selected' : '' }}>{{ $uk->nama_rumahsakit }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tahun</label>
                            <select name="tahun" class="form-select select2">
                                <option value="">Semua Tahun</option>
                                @foreach($tahuns as $tahun)
                                    <option value="{{ $tahun }}" {{ request('tahun') == $tahun ? 'selected' : '' }}>{{ $tahun }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Terapkan Filter
                            </button>
                            <a href="{{ route('pengangkatan.index') }}" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabel Permohonan --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Permohonan</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="tabel-pengangkatan">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nomor Permohonan</th>
                                <th>Jalur</th>
                                <th>Unit Kerja</th>
                                <th>Tanggal</th>
                                <th class="text-center">Jumlah Peserta</th>
                                <th>Status</th>
                                <th style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permohonan as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <span class="text-primary font-weight-bold">{{ $item->nomor_permohonan }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $item->jalur_badge_color }}">
                                        {{ $item->jalur_label }}
                                    </span>
                                </td>
                                <td>{{ $item->unitKerja->nama_rumahsakit }}</td>
                                <td>{{ $item->tanggal_permohonan->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    <span class="badge badge-info">{{ $item->peserta->count() }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $item->status_badge_color }}">
                                        {{ $item->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('pengangkatan.show', $item->id) }}"
                                           class="btn btn-info"
                                           title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($item->bisaDiedit())
                                            <a href="{{ route('pengangkatan.edit', $item->id) }}"
                                               class="btn btn-warning"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @can('delete pengangkatan')
                                        @if($item->bisaDihapus())
                                            <button type="button"
                                                    class="btn btn-danger"
                                                    onclick="confirmDelete({{ $item->id }})"
                                                    title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                        @endcan
                                        <a href="{{ route('pengangkatan.export', $item->id) }}"
                                           class="btn btn-secondary"
                                           title="Export PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
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
</section>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#tabel-pengangkatan').DataTable({
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            ordering: true,
            pageLength: 10,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
            }
        });

        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    });

    function confirmDelete(id) {
        swal({
            title: 'Hapus Permohonan?',
            text: 'Permohonan yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            buttons: true,
            dangerMode: true,
            buttons: ['Batal', 'Ya, Hapus!']
        })
        .then((willDelete) => {
            if (willDelete) {
                swal({
                    title: 'Menghapus...',
                    text: 'Mohon tunggu...',
                    icon: 'info',
                    buttons: false,
                    closeOnClickOutside: false
                });

                $.ajax({
                    url: '{{ route("pengangkatan.index") }}/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        swal('Berhasil!', 'Permohonan berhasil dihapus.', 'success')
                            .then(() => {
                                location.reload();
                            });
                    },
                    error: function(xhr) {
                        swal('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    }
</script>
@endpush
