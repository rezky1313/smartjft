@extends('layouts.users.master')

@section('title', 'Manajemen User')

@section('isi')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Manajemen User</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('user.peta') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Manajemen User</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar User</h3>
                @can('manage users')
                <a href="{{ route('user.manajemen-user.create') }}" class="btn btn-primary btn-sm float-right">
                    <i class="fas fa-plus"></i> Tambah User
                </a>
                @endcan
            </div>
            <div class="card-body">
                <table id="tableUser" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th width="200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $index => $user)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @foreach($user->roles as $role)
                                    @if($role->name == 'super_admin')
                                        <span class="badge badge-danger">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</span>
                                    @elseif($role->name == 'admin')
                                        <span class="badge badge-primary">{{ ucfirst($role->name) }}</span>
                                    @elseif($role->name == 'operator')
                                        <span class="badge badge-warning text-dark">{{ ucfirst($role->name) }}</span>
                                    @elseif($role->name == 'viewer')
                                        <span class="badge badge-success">{{ ucfirst($role->name) }}</span>
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                @if($user->status == 'active')
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                @can('manage users')
                                    <a href="{{ route('user.manajemen-user.edit', $user) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    @if($user->id !== auth()->id())
                                        <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#modalResetPassword{{ $user->id }}">
                                            <i class="fas fa-key"></i>
                                        </button>

                                        <form action="{{ route('user.manajemen-user.destroy', $user) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus user ini?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- Modal Reset Password untuk setiap user --}}
@foreach($users as $user)
@if($user->id !== auth()->id())
<div class="modal fade" id="modalResetPassword{{ $user->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form action="{{ route('user.manajemen-user.reset-password', $user) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password - {{ $user->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endforeach

@push('scripts')
<script>
$(document).ready(function() {
    $('#tableUser').DataTable({
        'responsive': true,
        'lengthChange': true,
        'autoWidth': false,
        'ordering': true,
        'info': true,
        'paging': true,
        'searching': true,
        'pageLength': 10,
        'language': {
            'url': '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
        }
    });
});
</script>
@endpush
@endsection
