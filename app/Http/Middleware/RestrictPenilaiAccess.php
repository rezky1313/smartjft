<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * UJ-ROLE: kunci akun yang HANYA berperan pewawancara/penguji (tanpa role lain)
 * supaya cuma bisa akses rute whitelist mereka, walau URL diketik langsung.
 * Berlaku global (didaftarkan di grup middleware 'web') supaya menutup SEMUA
 * rute auth-only di app ini, bukan cuma yang dibuat untuk fitur ini.
 */
class RestrictPenilaiAccess
{
    private const ROUTE_WHITELIST = [
        'penilai.index',
        'ujikom-online.admin.nilai-manual.form',
        'ujikom-online.admin.nilai-manual.store',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $this->isPenilaiOnlyAccount($user)) {
            $routeName = $request->route()?->getName();

            if (!in_array($routeName, self::ROUTE_WHITELIST, true)) {
                abort(403, 'Akun Pewawancara/Penguji hanya bisa mengakses halaman input nilai.');
            }
        }

        return $next($request);
    }

    private function isPenilaiOnlyAccount($user): bool
    {
        $roles = $user->getRoleNames();

        if ($roles->isEmpty()) {
            return false;
        }

        return $roles->diff(['pewawancara', 'penguji'])->isEmpty();
    }
}
