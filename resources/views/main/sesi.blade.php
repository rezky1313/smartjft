@extends('layouts.auth.master')
@section('title','Login Page')
@section('isi')

    <div class="box">
        <div class="container">
            <div class="top-header">
                <h1>Mohon Maaf!!</h1>
                <p>Anda tidak bisa mengakses Halaman Ini dikarenakan Sesi Anda Masih Aktif.
                    <br>
                    Silakan <a href="logout">Logout</a> atau hubungi administrator jika Anda membutuhkan bantuan.
                </p>
            </div>
        </div>
    </div>


@endsection
