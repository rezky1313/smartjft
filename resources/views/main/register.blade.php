@extends('layouts.auth.master')
@section('title','Registrasi')
@section('isi')


    <body>
        <div class="box">
            <div class="container">
                <div class="top-header">
                    <h3>Create Account</h3>
                    <h2>REGISTER</h2>
                </div>
                    @if (Session::has('success'))
                        <div class="alert alert-success">
                            {{session::get('success')}}
                        </div>
                    @endif
                <form action="{{route('register')}}" method="POST">
                    @csrf
                    <div class="input-field">
                        <input type="text" name="name" class="input" id="name" placeholder="Username" required>
                    </div>
                    <br>
                    <div class="input-field">
                        <input type="email" name="email" class="input" id="email" placeholder="Email" required>
                    </div>
                    <br>
                    <div class="input-field">
                        <input type="password" name="password" class="input" id="password" placeholder="Password" required>
                    </div>
                    <br>
                    <div class="input-field">
                        <input type="submit" class="submit" value="Create Account">
                    </div>

                    <div class="">
                        <a class="small" href="login">Have a account? Go to login</a>
                    </div>

                </form>

            </div>
        </div>




        {{-- <div id="layoutAuthentication">
            <div id="layoutAuthentication_content">
                <main>
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-lg-7">
                                <div class="card shadow-lg border-0 rounded-lg mt-5">
                                    <div class="card-header"><h3 class="text-center font-weight-light my-4">Create Account</h3></div>
                                    <div class="card-body">
                                        <form>
                                            <div class="form-floating mb-3">
                                                <div class="form-floating mb-3">
                                                    <div class="form-floating mb-3 mb-md-0">
                                                        <input class="form-control" id="inputFirstName" type="text" placeholder="Enter your first name" />
                                                        <label for="inputFirstName">First name</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-floating mb-3">
                                                <input class="form-control" id="inputEmail" type="email" placeholder="name@example.com" />
                                                <label for="inputEmail">Email address</label>
                                            </div>
                                            <div class="form-floating mb-3">
                                                <div class="form-floating mb-3">
                                                    <div class="form-floating mb-3 mb-md-0">
                                                        <input class="form-control" id="inputPassword" type="password" placeholder="Create a password" />
                                                        <label for="inputPassword">Password</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-4 mb-0">
                                                <div class="d-grid"><a class="btn btn-primary btn-block" href="login">Create Account</a></div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="card-footer text-center py-3">
                                        <div class="small"><a href="login">Have an account? Go to login</a></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
            <div id="layoutAuthentication_footer"> --}}

@endsection
