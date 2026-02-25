<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\Regency;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WilayahController extends Controller
{
    // public function regencies(Province $province): JsonResponse
    // {
    //     $items = Regency::where('province_id', $province->id)
    //         ->orderBy('type')->orderBy('name')
    //         ->get(['id','name','type'])
    //         ->map(fn($r) => ['id' => $r->id, 'text' => $r->type.' '.$r->name]);

    //     return response()->json($items);
    // }

//    public function regencies($provinceId)
// {
//     $rows = DB::table('regencies')
//         ->where('province_id', $provinceId)
//         ->orderBy('name')
//         ->get(['id','name']);

//     return response()->json($rows); // ← JSON array [{id,name},...]
// }

// public function units(\Illuminate\Http\Request $r)
// {
//     $q = DB::table('rumahsakits as u')
//         ->selectRaw('u.no_rs as id, u.nama_rumahsakit as text')
//         ->when($r->filled('regency_id'), fn($x)=>$x->where('u.regency_id',$r->regency_id))
//         ->when($r->filled('q'), fn($x)=>$x->where('u.nama_rumahsakit','like','%'.$r->q.'%'))
//         ->orderBy('u.nama_rumahsakit')->limit(30)->get();

//     return response()->json(['results'=>$q]); // format Select2
// }

public function regencies($provinceId)
{
    $rows = DB::table('regencies')
        ->where('province_id', $provinceId)
        ->orderBy('name')
        ->get(['id','name']);
    return response()->json($rows);               // ← array [{id,name}]
}

public function units(\Illuminate\Http\Request $r)
{
    $q = DB::table('rumahsakits as u')
        ->selectRaw('u.no_rs as id, u.nama_rumahsakit as text')
        ->when($r->filled('regency_id'), fn($x)=>$x->where('u.regency_id',$r->regency_id))
        ->when($r->filled('q'), fn($x)=>$x->where('u.nama_rumahsakit','like','%'.$r->q.'%'))
        ->orderBy('u.nama_rumahsakit')->limit(30)->get();
    return response()->json(['results'=>$q]);     // ← format Select2
}

}
