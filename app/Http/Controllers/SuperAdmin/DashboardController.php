<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SaasService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private SaasService $saas)
    {
    }

    public function __invoke(): View
    {
        $this->saas->ensurePlans();
        $this->saas->seedDemo(auth()->id());
        $stats = $this->saas->dashboardStats();

        return view('superadmin.dashboard', compact('stats'));
    }
}
