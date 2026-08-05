{{-- GreenPOS AI — panneau flottant (module isolé) --}}
@vite(['resources/css/ai.css', 'resources/js/ai.js'])
<div id="gp-ai-root"
     data-chat-url="{{ route('ai.chat') }}"
     data-context-url="{{ route('ai.context') }}"
     data-confirm-url-template="{{ url('/ai/actions/__ID__/confirm') }}"
     data-cancel-url-template="{{ url('/ai/actions/__ID__/cancel') }}"
     data-dashboard-url="{{ route('ai.dashboard') }}"
     data-route="{{ request()->route()?->getName() }}"
     data-path="{{ request()->path() }}"
     data-csrf="{{ csrf_token() }}">
    <button type="button" id="gp-ai-fab" class="gp-ai-fab" aria-label="Ouvrir GreenPOS AI" title="GreenPOS AI">
        <span class="gp-ai-fab-glow"></span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.5 15.5l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7.7-2z"/>
        </svg>
    </button>

    <div id="gp-ai-panel" class="gp-ai-panel" hidden role="dialog" aria-modal="true" aria-label="Assistant GreenPOS AI">
        <header class="gp-ai-header">
            <div class="gp-ai-brand">
                <span class="gp-ai-avatar">AI</span>
                <div>
                    <p class="gp-ai-title">GreenPOS AI</p>
                    <p id="gp-ai-context-label" class="gp-ai-subtitle">Contexte intelligent</p>
                </div>
            </div>
            <div class="gp-ai-header-actions">
                <select id="gp-ai-persona" class="gp-ai-select" aria-label="Persona"></select>
                <a href="{{ route('ai.dashboard') }}" class="gp-ai-icon-btn" title="Dashboard AI">▦</a>
                <button type="button" id="gp-ai-close" class="gp-ai-icon-btn" aria-label="Fermer">✕</button>
            </div>
        </header>

        <div id="gp-ai-hints" class="gp-ai-hints"></div>
        <div id="gp-ai-messages" class="gp-ai-messages" aria-live="polite"></div>

        <form id="gp-ai-form" class="gp-ai-composer">
            <textarea id="gp-ai-input" rows="1" placeholder="Demandez n’importe quoi sur GreenPOS…" maxlength="4000"></textarea>
            <button type="submit" id="gp-ai-send" class="gp-ai-send" aria-label="Envoyer">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M3.4 20.6l17.8-8.1c.8-.4.8-1.5 0-1.9L3.4 2.5c-.7-.3-1.4.3-1.2 1l1.7 6.4c.1.4.4.7.8.8l8.2 1.3-8.2 1.3c-.4.1-.7.4-.8.8L2.2 19.6c-.2.7.5 1.3 1.2 1z"/></svg>
            </button>
        </form>
    </div>
</div>
