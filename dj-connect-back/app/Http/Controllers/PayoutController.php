<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function create(Request $request)
    {
        $validated = $request->validate([
            'dj_id' => 'required|exists:djs,id',
            'amount' => 'required|numeric',
            'status' => 'required|in:pending,processed',
        ]);

        $payout = Payout::create($validated);

        return response()->json($payout);
    }

    public function updateStatus(Request $request, Payout $payout)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processed',
        ]);

        $payout->update($validated);

        return response()->json($payout);
    }
}
