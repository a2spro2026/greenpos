{{-- Logout confirmation modal --}}
<div id="gp-logout-modal" class="gp-session-modal" hidden aria-hidden="true" role="dialog" aria-labelledby="gp-logout-title">
    <div class="gp-session-modal-backdrop" data-logout-cancel></div>
    <div class="gp-session-modal-panel">
        <div class="gp-session-modal-icon" aria-hidden="true">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </div>
        <h2 id="gp-logout-title" class="text-lg font-bold text-gp-text">Se déconnecter</h2>
        <p class="mt-2 text-sm text-gp-muted">Voulez-vous vraiment vous déconnecter ?</p>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="gp-btn-secondary" data-logout-cancel>Annuler</button>
            <form method="POST" action="{{ route('logout') }}" id="gp-logout-form">
                @csrf
                <button type="submit" class="gp-btn-primary !bg-rose-600 hover:!bg-rose-700 w-full sm:w-auto">Se déconnecter</button>
            </form>
        </div>
    </div>
</div>
