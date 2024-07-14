<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
class UserController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'telegram_id' => 'required|unique:users',
            'name' => 'required',
            'phone_number' => 'required|unique:users',
        ]);

        $user = User::create($validated);

        return response()->json($user);
    }

    public function login(Request $request)
    {
        // Implementation for login
    }

    public function profile(User $user)
    {
        return response()->json($user);
    }
}
