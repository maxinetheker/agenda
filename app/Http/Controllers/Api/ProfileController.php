<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['user' => $request->user()->toAgentArray()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'office' => ['nullable', 'string', 'max:160'],
            'license_number' => ['nullable', 'string', 'max:80'],
            'photo_url' => ['nullable', 'string', 'max:500'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ]);

        $request->user()->fill($data)->save();

        return response()->json(['user' => $request->user()->fresh()->toAgentArray()]);
    }

    public function registerDevice(Request $request)
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        $request->user()->forceFill(['fcm_token' => $data['fcm_token']])->save();

        return response()->json(['ok' => true]);
    }
}
