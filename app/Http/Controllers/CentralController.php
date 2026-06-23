<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;


class CentralController extends Controller
{
     //Halaman Utamaa
     public function main(){
        return view('main.main');
    }

    //Login
    public function login(){
        return view('main.login');
    }

    public function LoginAksi(Request $request){
        $request->validate([
            'login'    => 'required',
            'password' => 'required',
        ], [
            'login.required'    => 'Email atau NIP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $loginField = $request->login;

        // Deteksi apakah input berupa email atau username/NIP
        $fieldType = filter_var($loginField, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $fieldType => $loginField,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user()->fresh();

            if ($user->roles->isEmpty()) {
                $user->assignRole('viewer');
            }

            return redirect()->route('user.peta');
        }

        return back()->withErrors([
            'login' => 'Email/NIP atau password salah.',
        ])->withInput();
    }

    //Register
    public function register(){
        return view('main.register');
    }

    public function registerAksi(Request $request){
        $user = new User();

        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->status = 'active';

        $user->save();

        // Assign default role 'viewer' untuk user yang baru register
        $user->assignRole('viewer');

        return back()->with('success','Register Successful');
    }

    //Logout
    function logout(){
        Auth::logout();
        return redirect('');
    }

    //View Error
    public function error(){
        return view('main.error');
    }

    //View Sesi Pembatas User
    public function sesi(){
        return view('main.sesi');
    }

     //FUNGSI LUPA PASSWORD
    public function showLinkRequestForm()
    {
        return view('main.lupa-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        set_time_limit(1000);

        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetForm(Request $request, $token)
    {
        return view('main.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:1',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password)
                ])->setRememberToken(\Illuminate\Support\Str::random(60));

                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET

            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }



}
