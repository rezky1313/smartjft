<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Rumahsakit;
use Illuminate\Support\Facades\DB;

class PetaDashboardController extends Controller
{
    public function index()
    {
        $rumahsakits = Rumahsakit::all();
        $jumlahrs = DB::table('rumahsakits')->count();
        $jumlahuser = DB::table('users')->count();

        return view('users.dashboard', compact('rumahsakits' ,'jumlahrs' , 'jumlahuser'));
    }
}
