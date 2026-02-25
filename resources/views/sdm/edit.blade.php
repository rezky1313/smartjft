@extends('layouts.users.master')
@section('title', 'Data Formasi Jabatan')
@section('isi')
{{-- @section('content') --}}
<div class="container mt-3">
    <h4>Edit SDM</h4>
    <form action="{{ route('user.sdm.update', $sdm->id) }}" method="POST" class="mt-3">
        @csrf @method('PUT')
        @include('sdm.form', ['mode' => 'edit'])
    </form>
</div>
@endsection
