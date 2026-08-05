<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformAdminService;
use App\Services\SaasService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private PlatformAdminService $platform,
        private SaasService $saas,
    ) {
    }

    public function __invoke(): View
    {
        $this->saas->ensurePlans();
        $stats = $this->platform->dashboardKpis();

        return view('admin.dashboard', compact('stats'));
    }
}
