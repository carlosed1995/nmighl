<?php

namespace App\Http\Controllers;

use App\Services\IprocessPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class IprocessWebhookController extends Controller
{
    public function __invoke(Request $request, IprocessPaymentService $iprocessPaymentService): JsonResponse
    {
        try {
            $order = $iprocessPaymentService->handleWebhook($request);
        } catch (RuntimeException $exception) {
            return response()->json([
                'ok' => false,
                'error' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'order_id' => $order->id,
            'ghl_invoice_id' => $order->ghl_invoice_id,
        ]);
    }
}
