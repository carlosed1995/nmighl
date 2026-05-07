<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ghl\UpdateInvoiceStatusRequest;
use App\Http\Requests\Api\Ghl\UpsertInvoiceRequest;
use App\Models\GhlClient;
use App\Models\GhlInvoice;
use App\Models\GhlLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GhlInvoiceController extends Controller
{
    public function upsert(UpsertInvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $invoice = DB::transaction(function () use ($data) {
            $location = GhlLocation::query()->firstOrCreate(
                ['ghl_id' => $data['ghl_location_id']],
                ['name' => '(pending sync)']
            );

            $client = GhlClient::query()->firstOrCreate(
                [
                    'ghl_location_id' => $location->id,
                    'ghl_contact_id'  => $data['ghl_contact_id'],
                ],
                ['name' => '(pending sync)']
            );

            return GhlInvoice::query()->updateOrCreate(
                ['ghl_invoice_id' => $data['ghl_invoice_id']],
                [
                    'ghl_client_id'   => $client->id,
                    'ghl_location_id' => $location->id,
                    'invoice_number'  => $data['invoice_number'] ?? null,
                    'issued_date'     => $data['issued_date'] ?? null,
                    'due_date'        => $data['due_date'] ?? null,
                    'amount'          => $data['amount'],
                    'status'          => $data['status'],
                    'raw'             => $data['raw'] ?? null,
                ]
            );
        });

        return response()->json([
            'id'             => $invoice->id,
            'ghl_invoice_id' => $invoice->ghl_invoice_id,
            'was_created'    => $invoice->wasRecentlyCreated,
        ], $invoice->wasRecentlyCreated ? 201 : 200);
    }

    public function updateStatus(UpdateInvoiceStatusRequest $request, string $ghlInvoiceId): JsonResponse
    {
        $invoice = GhlInvoice::query()->where('ghl_invoice_id', $ghlInvoiceId)->first();

        abort_if($invoice === null, 404, 'Invoice not found.');

        $invoice->status = $request->validated('status');
        $invoice->save();

        return response()->json([
            'id'     => $invoice->id,
            'status' => $invoice->status,
        ]);
    }

    public function destroy(string $ghlInvoiceId): JsonResponse
    {
        $invoice = GhlInvoice::query()->where('ghl_invoice_id', $ghlInvoiceId)->first();

        abort_if($invoice === null, 404, 'Invoice not found.');

        DB::transaction(fn () => $invoice->delete());

        return response()->json(['deleted' => true]);
    }
}
