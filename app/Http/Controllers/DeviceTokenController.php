<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'expo_push_token' => 'required|string',
            'platform' => 'nullable|string|in:ios,android,web',
        ]);

        $token = DeviceToken::updateOrCreate(
            ['expo_push_token' => $request->expo_push_token],
            [
                'platform' => $request->platform,
                'user_id' => Auth::id(), // null if guest
                'is_active' => true,
            ]
        );

        return response()->json([
            'message' => 'Token registered successfully',
            'data' => $token
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'expo_push_token' => 'required|string',
        ]);

        DeviceToken::where('expo_push_token', $request->expo_push_token)
            ->update(['is_active' => false]);

        return response()->json(['message' => 'Token unregistered successfully']);
    }
}
