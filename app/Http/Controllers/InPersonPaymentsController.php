<?php

namespace App\Http\Controllers;

class InPersonPaymentsController extends Controller
{
    public function index()
    {
        return view('nmi.in-person-payments');
    }
}
