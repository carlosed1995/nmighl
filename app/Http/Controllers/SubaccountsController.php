<?php

namespace App\Http\Controllers;

use App\Models\GhlClient;
use App\Models\GhlInvoice;
use App\Models\GhlLocation;

class SubaccountsController extends Controller
{
    public function index()
    {
        $locations = GhlLocation::orderBy('name')->get();

        return view('nmi.subaccounts', compact('locations'));
    }

    public function clients(GhlLocation $location)
    {
        $clients = GhlClient::where('ghl_location_id', $location->id)->latest()->get();

        return view('nmi.subaccount-clients', compact('location', 'clients'));
    }

    public function clientProfile(GhlLocation $location, GhlClient $client)
    {
        return view('nmi.client-profile', compact('location', 'client'));
    }

    public function clientInvoices(GhlLocation $location, GhlClient $client)
    {
        $invoices = GhlInvoice::where('ghl_client_id', $client->id)->latest()->get();

        return view('nmi.client-invoices', compact('location', 'client', 'invoices'));
    }
}
