<?php

/**
 * Smoke test CRM Enterprise.
 * Usage: php scripts/smoke_crm.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\User;
use App\Services\CrmService;
use App\Support\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$ok = 0;
$fail = 0;

function assertTrue(bool $cond, string $msg): void
{
    global $ok, $fail;
    if ($cond) {
        echo "  OK  {$msg}\n";
        $ok++;
    } else {
        echo " FAIL {$msg}\n";
        $fail++;
    }
}

echo "=== CRM Enterprise smoke ===\n";

foreach (['crm_leads', 'crm_opportunities', 'crm_activities', 'crm_email_templates', 'crm_email_logs', 'crm_goals'] as $t) {
    assertTrue(Schema::hasTable($t), "table {$t}");
}

$user = User::query()->where('email', 'admin@greenpos.test')->first() ?? User::query()->first();
assertTrue((bool) $user, 'user');
Auth::login($user);

// Ensure workspace company session
$company = $user->companies()->first();
if ($company) {
    session(['workspace_company_id' => $company->id]);
}
assertTrue((bool) Workspace::company(), 'workspace company');

$crm = app(CrmService::class);
$crm->seedDemoIfEmpty();

assertTrue(CrmLead::query()->forCompany($crm->companyId())->count() > 0, 'demo leads');
assertTrue(CrmOpportunity::query()->forCompany($crm->companyId())->count() > 0, 'demo opportunities');

$stats = $crm->dashboardStats();
foreach (['prospects', 'active_leads', 'opportunities', 'conversion_rate', 'pipeline_value', 'goal'] as $k) {
    assertTrue(array_key_exists($k, $stats), "dashboard {$k}");
}

$board = $crm->pipelineBoard();
assertTrue(count($board) === 7, '7 pipeline stages');

$opp = CrmOpportunity::query()->forCompany($crm->companyId())->whereNotIn('stage', ['won', 'lost'])->first();
assertTrue((bool) $opp, 'open opportunity');
$moved = $crm->moveOpportunity($opp, 'negotiation');
assertTrue($moved->stage === 'negotiation', 'drag move stage');

$lead = CrmLead::query()->forCompany($crm->companyId())->whereNull('archived_at')->where('status', '!=', 'converted')->first();
assertTrue((bool) $lead, 'convertible lead');
$crm->qualifyLead($lead);
assertTrue($lead->fresh()->status === 'qualified', 'qualify lead');

$estimate = $crm->estimateWinProbability($moved);
assertTrue(isset($estimate['probability'], $estimate['advice']), 'AI estimate');

$routes = ['crm.dashboard', 'crm.pipeline', 'crm.leads.index', 'crm.calendar', 'crm.emails.index', 'crm.reports'];
foreach ($routes as $r) {
    assertTrue(Route::has($r), "route {$r}");
}

view()->share('errors', new Illuminate\Support\ViewErrorBag([]));
$view = view('crm.dashboard', ['stats' => $stats])->render();
assertTrue(str_contains($view, 'Dashboard CRM'), 'dashboard view');

$pipeView = view('crm.pipeline', ['columns' => $board])->render();
assertTrue(str_contains($pipeView, 'crm-pipeline'), 'pipeline view');

echo "\nResult: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);
