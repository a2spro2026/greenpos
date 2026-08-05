<?php

/**
 * Smoke test GreenPOS AI module.
 * Usage: php scripts/smoke_ai.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Ai\AiProviderManager;
use App\Models\AiPrompt;
use App\Models\User;
use App\Services\AiAssistantService;
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

echo "=== GreenPOS AI smoke ===\n";

assertTrue(Schema::hasTable('ai_conversations'), 'ai_conversations');
assertTrue(Schema::hasTable('ai_messages'), 'ai_messages');
assertTrue(Schema::hasTable('ai_prompts'), 'ai_prompts');
assertTrue(Schema::hasTable('ai_providers'), 'ai_providers');
assertTrue(Schema::hasTable('ai_suggestions'), 'ai_suggestions');
assertTrue(Schema::hasTable('ai_action_logs'), 'ai_action_logs');

$user = User::query()->where('email', 'admin@greenpos.test')->first() ?? User::query()->first();
assertTrue((bool) $user, 'user exists');
Auth::login($user);

$ai = app(AiAssistantService::class);
$ai->ensureCatalog();

assertTrue(AiPrompt::query()->count() >= 5, '5 personas seeded');
assertTrue(count(app(AiProviderManager::class)->codes()) === 7, '7 providers registered');

$routes = ['ai.dashboard', 'ai.chat', 'ai.context', 'ai.providers'];
foreach ($routes as $r) {
    assertTrue(Route::has($r), "route {$r}");
}

$ctx = $ai->resolveContext('products.index', 'products');
assertTrue($ctx['module'] === 'products', 'context products');
assertTrue($ctx['persona'] === 'stock', 'persona stock on products');

$reply = $ai->chat([
    'message' => 'Quels sont mes meilleurs produits ?',
    'context_route' => 'products.index',
    'context_path' => 'products',
]);
assertTrue(! empty($reply['conversation_id']), 'chat conversation created');
assertTrue(str_contains($reply['message']['content'], 'Meilleurs produits') || str_contains($reply['message']['content'], 'produits'), 'analytics reply');

$create = $ai->chat([
    'message' => 'Créer un client appelé Atlas Demo',
    'conversation_id' => $reply['conversation_id'],
    'context_route' => 'customers.index',
    'context_path' => 'customers',
]);
$actions = $create['message']['actions'] ?? [];
assertTrue(collect($actions)->contains(fn ($a) => ($a['requires_confirmation'] ?? false) === true), 'automation requires confirmation');

$dash = $ai->dashboardStats();
assertTrue(isset($dash['prompts'], $dash['providers'], $dash['insights']), 'dashboard stats');

view()->share('errors', new Illuminate\Support\ViewErrorBag([]));
$view = view('ai.dashboard', ['stats' => $dash])->render();
assertTrue(str_contains($view, 'GreenPOS AI'), 'dashboard view');

$widget = view('ai.widget')->render();
assertTrue(str_contains($widget, 'gp-ai-fab'), 'widget fab');

echo "\nResult: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);
