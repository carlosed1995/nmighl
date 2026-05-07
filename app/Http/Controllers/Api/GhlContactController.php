<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ghl\UpsertContactRequest;
use App\Models\GhlClient;
use App\Models\GhlLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GhlContactController extends Controller
{
    public function upsert(UpsertContactRequest $request): JsonResponse
    {
        $data = $request->validated();

        $client = DB::transaction(function () use ($data) {
            $location = GhlLocation::query()->firstOrCreate(
                ['ghl_id' => $data['ghl_location_id']],
                ['name' => '(pending sync)']
            );

            return GhlClient::query()->updateOrCreate(
                [
                    'ghl_location_id' => $location->id,
                    'ghl_contact_id'  => $data['ghl_contact_id'],
                ],
                [
                    'name'             => $data['name'] ?? null,
                    'email'            => $data['email'] ?? null,
                    'phone'            => $data['phone'] ?? null,
                    'tags'             => $data['tags'] ?? null,
                    'last_activity_at' => $data['last_activity_at'] ?? null,
                    'raw'              => $data['raw'] ?? null,
                ]
            );
        });

        return response()->json([
            'id'             => $client->id,
            'ghl_contact_id' => $client->ghl_contact_id,
            'was_created'    => $client->wasRecentlyCreated,
        ], $client->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(string $ghlContactId): JsonResponse
    {
        $client = GhlClient::query()->where('ghl_contact_id', $ghlContactId)->first();

        abort_if($client === null, 404, 'Contact not found.');

        DB::transaction(fn () => $client->delete());

        return response()->json(['deleted' => true]);
    }
}
