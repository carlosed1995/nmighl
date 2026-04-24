<?php

namespace App\Http\Controllers;

use App\Models\GhlClient;
use App\Models\NmiPaymentOrder;
use App\Services\NmiGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class OnlinePaymentsController extends Controller
{
    public function index()
    {
        return view('nmi.online-payments', [
            'clients' => GhlClient::query()->orderBy('name')->limit(500)->get(),
            'orders' => NmiPaymentOrder::query()->with('client.location')->latest()->limit(20)->get(),
        ]);
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ghl_client_id' => ['required', 'integer', 'exists:ghl_clients,id'],
            'amount' => ['required', 'numeric', 'min:0.5'],
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
            'ghl_order_id' => ['nullable', 'string', 'max:128'],
        ]);

        $client = GhlClient::query()->with('location')->findOrFail($data['ghl_client_id']);

        if (! $client->location || $client->location->ghl_id === '') {
            return redirect()->route('online-payments')->with('error', 'Selected client does not have a valid sub-account.');
        }

        NmiPaymentOrder::query()->create([
            'user_id' => $request->user()->id,
            'ghl_client_id' => $client->id,
            'ghl_contact_id' => $client->ghl_contact_id,
            'ghl_location_id' => $client->location->ghl_id,
            'amount' => $data['amount'],
            'currency' => strtoupper((string) ($data['currency'] ?? 'USD')),
            'description' => (string) ($data['description'] ?? 'GHL order'),
            'ghl_order_id' => (string) ($data['ghl_order_id'] ?? ''),
            'source' => 'manual',
            'status' => NmiPaymentOrder::STATUS_PENDING,
            'nmi_order_id' => ! empty($data['ghl_order_id']) ? 'ghl-order-'.$data['ghl_order_id'] : null,
        ]);

        return redirect()->route('online-payments')->with('status', 'Order created. You can now process payment with NMI.');
    }

    public function charge(Request $request, NmiGatewayService $gatewayService): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:nmi_payment_orders,id'],
            'customer_vault_id' => ['nullable', 'string', 'max:128'],
            'cc_number' => ['nullable', 'string', 'max:25'],
            'cc_exp' => ['nullable', 'string', 'max:10'],
            'cc_cvv' => ['nullable', 'string', 'max:10'],
        ]);

        $order = NmiPaymentOrder::query()->with('client')->findOrFail($data['order_id']);

        try {
            $gatewayService->chargeOrder($order, [
                'customer_vault_id' => $data['customer_vault_id'] ?? null,
                'cc_number' => $data['cc_number'] ?? null,
                'cc_exp' => $data['cc_exp'] ?? null,
                'cc_cvv' => $data['cc_cvv'] ?? null,
            ]);
        } catch (RuntimeException $exception) {
            return redirect()->route('online-payments')->with('error', $exception->getMessage());
        }

        return redirect()->route('online-payments')->with('status', 'Charge submitted to NMI. Check status below and webhook logs.');
    }
}
