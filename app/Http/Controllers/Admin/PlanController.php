<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaasPlan;
use App\Services\SaasService;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        app(SaasService::class)->ensurePlans();
        $plans = SaasPlan::query()->orderBy('sort_order')->get();

        return view('admin.plans.index', compact('plans'));
    }
}
