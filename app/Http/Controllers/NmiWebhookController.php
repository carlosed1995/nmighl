<?php

namespace App\Http\Controllers;

use App\Services\NmiGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NmiWebhookController extends Controller
{
    public function __invoke(Request $request, NmiGatewayService $gatewayService): Response
    {
        $gatewayService->handleWebhook($request);

        return response('ok', 200);
    }
}
