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
                'email'=>'required',
                'password'=>'required'
            ],
            [
                'email.required' => 'Email Wajib Diisi',
                'password.required' => 'Password Wajib Diisi',
            ]);

            $infologin = [
                'email' => $request->email,
                'password' => $request->password,
            ];

            if(Auth::attempt($infologin)){
                // Reload user untuk mendapatkan data terbaru termasuk roles dari Spatie
                $user = Auth::user()->fresh();

                // Cek apakah user punya role Spatie
                if ($user->roles->isEmpty()) {
                    // Jika user belum punya role, assign default role 'viewer'
                    $user->assignRole('viewer');
                }

                // Redirect semua user ke dashboard
                return redirect()->route('user.peta');
            }else{
                return redirect('')->withErrors('Username Atau Password Yang Dimasukkan Tidak Sesuai')->withInput();
            }
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
