<?php

namespace App\Http\Controllers;

use App\Models\Track;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'artist' => 'required',
            'duration' => 'nullable',
        ]);

        $track = Track::create($validated);

        return response()->json($track);
    }

    public function update(Request $request, Track $track)
    {
        $validated = $request->validate([
            'name' => 'nullable',
            'artist' => 'nullable',
            'duration' => 'nullable',
        ]);

        $track->update($validated);

        return response()->json($track);
    }
}
