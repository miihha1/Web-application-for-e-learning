<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'name', 'email', 'role', 'active', 'created_at')
            ->orderBy('id')
            ->get();

        return Inertia::render('admin/Users', [
            'users' => $users,
        ]);
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return back()->withErrors(['email' => 'You cannot deactivate your own account.']);
        }

        $user->active = !$user->active;
        $user->save();

        return back();
    }

    public function setRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', 'in:student,teacher,admin'],
        ]);

        if ($request->user()->id === $user->id) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }

        $user->role = $data['role'];
        $user->save();

        return back();
    }
}
