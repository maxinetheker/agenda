<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->contacts()->latest();

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        return response()->json([
            'contacts' => $query->get()->map->toApiArray()->values(),
        ]);
    }

    public function show(Request $request, Contact $contact)
    {
        $this->authorizeContact($request, $contact);

        $contact->load(['appointments' => fn ($q) => $q->orderBy('starts_at'), 'tasks' => fn ($q) => $q->orderBy('due_at')]);

        return response()->json([
            'contact' => $contact->toApiArray(),
            'appointments' => $contact->appointments->map->toApiArray()->values(),
            'tasks' => $contact->tasks->map->toApiArray()->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        $data['source'] = $data['source'] ?? 'manual';

        $contact = Contact::query()->create($data);

        return response()->json(['contact' => $contact->toApiArray()], 201);
    }

    public function update(Request $request, Contact $contact)
    {
        $this->authorizeContact($request, $contact);
        $contact->fill($this->validated($request, false))->save();

        return response()->json(['contact' => $contact->fresh()->toApiArray()]);
    }

    public function destroy(Request $request, Contact $contact)
    {
        $this->authorizeContact($request, $contact);
        $contact->delete();

        return response()->json(['ok' => true]);
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'contacts' => ['required', 'array', 'min:1'],
            'contacts.*.name' => ['required', 'string', 'max:160'],
            'contacts.*.phone' => ['nullable', 'string', 'max:40'],
            'contacts.*.email' => ['nullable', 'email', 'max:160'],
            'contacts.*.device_contact_id' => ['nullable', 'string', 'max:80'],
        ]);

        $imported = [];
        $userId = $request->user()->id;

        foreach ($data['contacts'] as $row) {
            $phone = $row['phone'] ?? null;
            $deviceId = $row['device_contact_id'] ?? null;

            $existing = $request->user()->contacts()
                ->when($deviceId, fn ($q) => $q->where('device_contact_id', $deviceId))
                ->when(! $deviceId && $phone, fn ($q) => $q->where('phone', $phone))
                ->first();

            if ($existing) {
                $existing->fill([
                    'name' => $row['name'],
                    'phone' => $phone ?: $existing->phone,
                    'email' => $row['email'] ?? $existing->email,
                    'source' => 'telefono',
                ])->save();
                $imported[] = $existing->fresh()->toApiArray();
                continue;
            }

            $contact = Contact::query()->create([
                'user_id' => $userId,
                'name' => $row['name'],
                'phone' => $phone,
                'email' => $row['email'] ?? null,
                'device_contact_id' => $deviceId,
                'source' => 'telefono',
                'type' => 'lead',
                'stage' => 'nuevo',
            ]);
            $imported[] = $contact->toApiArray();
        }

        return response()->json([
            'imported' => count($imported),
            'contacts' => $imported,
        ]);
    }

    public function markCalled(Request $request, Contact $contact)
    {
        $this->authorizeContact($request, $contact);
        $contact->forceFill(['last_contacted_at' => now()])->save();

        return response()->json(['contact' => $contact->fresh()->toApiArray()]);
    }

    private function validated(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'type' => ['nullable', 'string', 'max:40'],
            'stage' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
            'property_interest' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'string', 'max:80'],
            'source' => ['nullable', 'string', 'max:40'],
            'device_contact_id' => ['nullable', 'string', 'max:80'],
        ]);
    }

    private function authorizeContact(Request $request, Contact $contact): void
    {
        abort_unless($contact->user_id === $request->user()->id, 404);
    }
}
