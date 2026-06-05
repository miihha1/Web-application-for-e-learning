<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TeacherActivationController extends Controller
{
    public function create()
    {
        return Inertia::render('auth/BecomeTeacher');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $expected = env('TEACHER_CODE');

        if (!$expected || $data['code'] !== $expected) {
            return back()->withErrors(['code' => 'Invalid code.']);
        }

        $user = $request->user();

        if ($user->role === 'student') {
            $user->role = 'teacher';
            $user->save();
        }

        return redirect()->route('dashboard');
    }
}
