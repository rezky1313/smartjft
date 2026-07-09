
@extends('layouts.auth.master')
@section('title','Login Page')
@section('isi')

<div class="box">
    <div class="container">
        <div class="top-header">
            <h3>Have an account?</h3>
            <h1>LOGIN</h1>
            @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $item)
                                    <li>{{$item}}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif
        </div>
        <form action="" method="POST">
            @csrf
            <div class="input-field">
                <input type="email" name="email" class="input" id="email" placeholder="Email" required>
            </div>
            <br>
            <div class="input-field">
                <input type="password" name="password" class="input" id="password" placeholder="Password" required>
            </div>
            <br>
            <div class="input-field">
                <input type="submit" class="submit" value="Login">
            </div>

            <div class="buttom">
                <div class="left">
                    <input class="form-check-input" id="inputRememberPassword" type="checkbox" value="" />
                    <label class="form-check-label" for="inputRememberPassword">Remember Password</label>
                </div>
            </div>
            <div class="card-footer text-center py-3">
                <a class="small" href="forgot-password">Forgot Password?</a>
            </div>

            <div class="card-footer text-center py-3">
                <div class="small"><a href="register">Need an account? Sign up!</a></div>
            </div>

        </form>

    </div>
</div>

@endsection



