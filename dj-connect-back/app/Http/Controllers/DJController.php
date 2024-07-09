<?php

namespace App\Http\Controllers;

use App\Models\DJ;
use Illuminate\Http\Request;

class DJController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'stage_name' => 'required',
            'city' => 'required',
            'base_prices' => 'required|json',
            'payment_details' => 'required',
        ]);

        $dj = DJ::create($validated);

        return response()->json($dj);
    }

    public function profile(DJ $dj)
    {
        return response()->json($dj);
    }

    public function updateProfile(Request $request, DJ $dj)
    {
        $validated = $request->validate([
            'stage_name' => 'nullable',
            'city' => 'nullable',
            'base_prices' => 'nullable|json',
            'payment_details' => 'nullable',
        ]);

        $dj->update($validated);

        return response()->json($dj);
    }
}
