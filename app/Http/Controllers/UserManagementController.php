<?php

namespace App\Http\Controllers;

use App\Support\OcmsAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    /**
     * Tampilkan daftar semua user.
     */
    public function index()
    {
        $users = User::with('roles')->latest()->get();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Form tambah user baru.
     */
    public function create()
    {
        $roles = Role::query()
            ->whereIn('name', OcmsAccess::ALL_ROLES)
            ->get()
            ->sortBy(fn ($role) => array_search($role->name, OcmsAccess::ALL_ROLES, true))
            ->values();

        $roleDescriptions = OcmsAccess::roleDescriptions();

        return view('admin.users.create', compact('roles', 'roleDescriptions'));
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'nik'      => 'required|string|max:50|unique:users,nik',
            'password' => 'required|string|min:6|confirmed',
            'role'     => ['required', Rule::in(OcmsAccess::ALL_ROLES)],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'nik'      => strtoupper(trim($request->nik)),
            // Cast 'hashed' pada model User otomatis melakukan hashing
            'password' => $request->password,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('admin.users.index')
            ->with('success', 'User "' . $user->name . '" (' . $user->nik . ') berhasil didaftarkan dengan role ' . $request->role);
    }

    /**
     * Form ganti password user (SuperAdmin).
     */
    public function editPassword(User $user)
    {
        return view('admin.users.password', compact('user'));
    }

    /**
     * Simpan password baru untuk user (SuperAdmin).
     */
    public function updatePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Cast 'hashed' pada model User otomatis melakukan hashing
        $user->update(['password' => $request->password]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Password user "' . $user->name . '" (' . $user->nik . ') berhasil diganti.');
    }

    /**
     * Hapus user.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['delete' => 'Tidak bisa menghapus akun Anda sendiri.']);
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }
}
