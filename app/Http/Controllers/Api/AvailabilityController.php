<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityRange;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $ranges = $request->user()
            ->availabilityRanges()
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get()
            ->map->toApiArray()
            ->values();

        return response()->json(['ranges' => $ranges]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'weekday' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_minutes' => ['nullable', 'integer', 'min:15', 'max:240'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $range = $request->user()->availabilityRanges()->create($data);

        return response()->json(['range' => $range->toApiArray()], 201);
    }

    public function update(Request $request, AvailabilityRange $availabilityRange)
    {
        abort_unless($availabilityRange->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'weekday' => ['sometimes', 'integer', 'min:0', 'max:6'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'slot_minutes' => ['nullable', 'integer', 'min:15', 'max:240'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $availabilityRange->fill($data)->save();

        return response()->json(['range' => $availabilityRange->fresh()->toApiArray()]);
    }

    public function destroy(Request $request, AvailabilityRange $availabilityRange)
    {
        abort_unless($availabilityRange->user_id === $request->user()->id, 404);
        $availabilityRange->delete();

        return response()->json(['ok' => true]);
    }
}
