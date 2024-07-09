<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    public function create(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'dj_id' => 'required|exists:djs,id',
            'track_id' => 'nullable|exists:tracks,id',
            'status' => 'required|in:new,accepted,rejected,paid,completed',
            'price' => 'required|numeric',
        ]);

        $order = Order::create($validated);

        return response()->json($order);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,accepted,rejected,paid,completed',
        ]);

        $order->update($validated);

        return response()->json($order);
    }

    public function history(User $user)
    {
        $orders = Order::where('user_id', $user->id)->get();

        return response()->json($orders);
    }
}
