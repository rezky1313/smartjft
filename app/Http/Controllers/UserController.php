<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sdmmodels;
use App\Models\UnitKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles', 'sdm', 'unitKerja')->orderBy('name')->get();
        return view('users.manajemen-user.index', compact('users'));
    }

    public function create()
    {
        $roles      = Role::orderBy('name')->get();
        $unitKerjas = UnitKerja::orderBy('nama_unit_kerja')->get();
        return view('users.manajemen-user.form', compact('roles', 'unitKerjas'));
    }

    public function store(Request $request)
    {
        $role = $request->role;

        if ($role === 'pemangku') {
            $request->validate([
                'sdm_id' => 'required|exists:sumber_daya_manusia,id',
                'email'  => 'nullable|string|email|max:255|unique:users',
                'role'   => 'required|exists:roles,name',
                'status' => 'required|in:active,inactive',
            ], [
                'sdm_id.required' => 'Data pegawai JFT wajib dipilih untuk role Pemangku.',
            ]);

            $sdm  = Sdmmodels::findOrFail($request->sdm_id);
            $user = User::create([
                'name'     => $sdm->nama_lengkap,
                'email'    => $request->email ?? null,
                'username' => $sdm->nip,
                'sdm_id'   => $sdm->id,
                'password' => Hash::make($sdm->nip),
                'status'   => $request->status,
            ]);

        } elseif ($role === 'admin_unit') {
            $request->validate([
                'name'         => 'required|string|max:255',
                'email'        => 'required|string|email|max:255|unique:users',
                'password'     => 'required|string|min:6|confirmed',
                'unit_kerja_id'=> 'required|exists:unit_kerja,id',
                'role'         => 'required|exists:roles,name',
                'status'       => 'required|in:active,inactive',
            ], [
                'unit_kerja_id.required' => 'Unit Kerja wajib dipilih untuk role Admin Unit.',
            ]);

            $user = User::create([
                'name'         => $request->name,
                'email'        => $request->email,
                'unit_kerja_id'=> $request->unit_kerja_id,
                'password'     => Hash::make($request->password),
                'status'       => $request->status,
            ]);

        } else {
            $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
                'role'     => 'required|exists:roles,name',
                'status'   => 'required|in:active,inactive',
            ]);

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'status'   => $request->status,
            ]);
        }

        $user->assignRole($role);

        return redirect()->route('user.manajemen-user.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $roles      = Role::orderBy('name')->get();
        $unitKerjas = UnitKerja::orderBy('nama_unit_kerja')->get();
        $user->load('roles', 'sdm', 'unitKerja');
        return view('users.manajemen-user.form', compact('user', 'roles', 'unitKerjas'));
    }

    public function update(Request $request, User $user)
    {
        $role = $request->role;

        if ($role === 'pemangku') {
            $request->validate([
                'sdm_id' => 'required|exists:sumber_daya_manusia,id',
                'email'  => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
                'role'   => 'required|exists:roles,name',
                'status' => 'required|in:active,inactive',
            ], [
                'sdm_id.required' => 'Data pegawai JFT wajib dipilih untuk role Pemangku.',
            ]);

            $sdm = Sdmmodels::findOrFail($request->sdm_id);
            $user->update([
                'name'         => $sdm->nama_lengkap,
                'email'        => $request->email ?? $user->email,
                'username'     => $sdm->nip,
                'sdm_id'       => $sdm->id,
                'unit_kerja_id'=> null,
                'status'       => $request->status,
            ]);

        } elseif ($role === 'admin_unit') {
            $request->validate([
                'name'         => 'required|string|max:255',
                'email'        => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'unit_kerja_id'=> 'required|exists:unit_kerja,id',
                'role'         => 'required|exists:roles,name',
                'status'       => 'required|in:active,inactive',
            ], [
                'unit_kerja_id.required' => 'Unit Kerja wajib dipilih untuk role Admin Unit.',
            ]);

            $user->update([
                'name'         => $request->name,
                'email'        => $request->email,
                'unit_kerja_id'=> $request->unit_kerja_id,
                'sdm_id'       => null,
                'username'     => null,
                'status'       => $request->status,
            ]);

        } else {
            $request->validate([
                'name'   => 'required|string|max:255',
                'email'  => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'role'   => 'required|exists:roles,name',
                'status' => 'required|in:active,inactive',
            ]);

            $user->update([
                'name'         => $request->name,
                'email'        => $request->email,
                'unit_kerja_id'=> null,
                'sdm_id'       => null,
                'username'     => null,
                'status'       => $request->status,
            ]);
        }

        $user->syncRoles([$role]);

        return redirect()->route('user.manajemen-user.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('user.manajemen-user.index')
                ->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('user.manajemen-user.index')
            ->with('success', 'User berhasil dihapus.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('user.manajemen-user.index')
            ->with('success', 'Password user berhasil direset.');
    }
}
