<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiActionLog;
use App\Models\AiConversation;
use App\Models\AiProvider;
use App\Models\AiSuggestion;
use App\Services\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiController extends Controller
{
    public function __construct(private AiAssistantService $ai)
    {
    }

    public function dashboard(): View
    {
        $stats = $this->ai->dashboardStats();

        return view('ai.dashboard', compact('stats'));
    }

    public function conversation(AiConversation $conversation): View
    {
        abort_unless($conversation->user_id === auth()->id(), 403);
        $conversation->load(['messages', 'prompt']);
        $stats = $this->ai->dashboardStats();

        return view('ai.conversation', compact('conversation', 'stats'));
    }

    public function context(Request $request): JsonResponse
    {
        $context = $this->ai->resolveContext(
            $request->string('route')->toString() ?: null,
            $request->string('path')->toString() ?: null
        );

        return response()->json([
            'context' => $context,
            'prompts' => \App\Models\AiPrompt::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name', 'persona', 'icon']),
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer'],
            'persona' => ['nullable', 'string', 'max:64'],
            'provider' => ['nullable', 'string', 'max:32'],
            'context_route' => ['nullable', 'string', 'max:120'],
            'context_path' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->ai->chat($data);

        return response()->json($result);
    }

    public function confirmAction(Request $request, AiActionLog $action): JsonResponse
    {
        abort_unless($action->user_id === auth()->id(), 403);
        $result = $this->ai->confirmAction($action->id);

        return response()->json($result);
    }

    public function cancelAction(AiActionLog $action): JsonResponse
    {
        abort_unless($action->user_id === auth()->id(), 403);
        $this->ai->cancelAction($action->id);

        return response()->json(['ok' => true]);
    }

    public function dismissSuggestion(AiSuggestion $suggestion): RedirectResponse
    {
        $suggestion->update(['is_dismissed' => true, 'is_read' => true]);

        return back()->with('success', 'Suggestion masquée.');
    }

    public function providers(): View
    {
        $this->ai->ensureCatalog();
        $providers = AiProvider::query()->orderBy('code')->get();

        return view('ai.providers', compact('providers'));
    }

    public function updateProvider(Request $request, AiProvider $provider): RedirectResponse
    {
        $data = $request->validate([
            'is_enabled' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'model' => ['nullable', 'string', 'max:120'],
            'base_url' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:512'],
        ]);

        $creds = $provider->credentials ?? [];
        if (! empty($data['api_key'])) {
            $creds['api_key'] = $data['api_key'];
        }

        if ($request->boolean('is_default')) {
            AiProvider::query()->where('id', '!=', $provider->id)->update(['is_default' => false]);
        }

        $provider->update([
            'is_enabled' => $request->boolean('is_enabled') || $provider->code === 'local',
            'is_default' => $request->boolean('is_default') || $provider->code === 'local' && ! AiProvider::query()->where('is_default', true)->where('id', '!=', $provider->id)->exists(),
            'model' => $data['model'] ?? $provider->model,
            'base_url' => $data['base_url'] ?? $provider->base_url,
            'credentials' => $creds,
            'status' => $request->boolean('is_enabled') || $provider->code === 'local' ? 'connected' : 'ready',
        ]);

        return back()->with('success', 'Provider '.$provider->name.' mis à jour.');
    }
}
