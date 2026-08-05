<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaasPayment;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $payments = SaasPayment::query()->with('tenant')->latest()->paginate(30);

        return view('admin.payments.index', compact('payments'));
    }
}
