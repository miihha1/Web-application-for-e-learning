<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function storeImage(Request $request)
    {
        $user = $request->user();

        if (!$user || !($user->isTeacher() || $user->isAdmin())) {
            abort(403);
        }

        $data = $request->validate([
            'image' => ['required', 'image', 'max:2048'],
        ]);

        $directory = 'uploads/editor-images';
        File::ensureDirectoryExists(public_path($directory));

        $filename = Str::uuid() . '.' . $data['image']->getClientOriginalExtension();
        $data['image']->move(public_path($directory), $filename);

        return response()->json([
            'url' => asset(trim($directory . '/' . $filename, '/')),
        ]);
    }
}
