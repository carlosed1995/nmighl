<?php

namespace App\Http\Controllers;

class OnlinePaymentsController extends Controller
{
    public function index()
    {
        return view('nmi.online-payments');
    }
}
