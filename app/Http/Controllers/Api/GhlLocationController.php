<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ghl\UpsertLocationRequest;
use App\Models\GhlLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GhlLocationController extends Controller
{
    public function upsert(UpsertLocationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $location = DB::transaction(function () use ($data) {
            return GhlLocation::query()->updateOrCreate(
                ['ghl_id' => $data['ghl_id']],
                [
                    'name'       => $data['name'],
                    'company_id' => $data['company_id'] ?? null,
                    'timezone'   => $data['timezone'] ?? null,
                    'raw'        => $data['raw'] ?? null,
                ]
            );
        });

        return response()->json([
            'id'          => $location->id,
            'ghl_id'      => $location->ghl_id,
            'was_created' => $location->wasRecentlyCreated,
        ], $location->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(string $ghlId): JsonResponse
    {
        $location = GhlLocation::query()->where('ghl_id', $ghlId)->first();

        abort_if($location === null, 404, 'Location not found.');

        DB::transaction(fn () => $location->delete());

        return response()->json(['deleted' => true]);
    }
}
