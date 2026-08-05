<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\SystemAlert;
use App\Models\SystemBackup;
use App\Services\BackupService;
use App\Services\SystemHealthService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SystemHealthController extends Controller
{
    public function __construct(
        private BackupService $backups,
        private SystemHealthService $health,
    ) {
    }

    public function dashboard(): View
    {
        $this->authorizeView();
        $company = Workspace::company();
        abort_unless($company, 403);

        $dash = $this->health->dashboard($company);
        $recentBackups = SystemBackup::query()
            ->forCompany($company->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('system.dashboard', [
            'company' => $company,
            'health' => $dash['health'],
            'alerts' => $dash['alerts'],
            'incidents' => $dash['incidents'],
            'snapshots' => $dash['snapshots'],
            'recentBackups' => $recentBackups,
            'policy' => $this->backups->policy($company),
        ]);
    }

    public function backups(): View
    {
        $this->authorizeView();
        $company = Workspace::company();
        abort_unless($company, 403);

        $items = SystemBackup::query()
            ->forCompany($company->id)
            ->with('creator:id,name')
            ->latest()
            ->paginate(20);

        return view('system.backups', [
            'company' => $company,
            'backups' => $items,
            'policy' => $this->backups->policy($company),
            'schedules' => $this->backups->scheduleOptions(),
        ]);
    }

    public function storeBackup(Request $request): RedirectResponse
    {
        $this->authorizeUpdate();
        $company = Workspace::company();
        abort_unless($company, 403);

        $data = $request->validate([
            'include_files' => ['nullable', 'boolean'],
        ]);

        try {
            $backup = $this->backups->createManual(
                $company,
                $request->user(),
                (bool) ($data['include_files'] ?? true)
            );

            return redirect()
                ->route('system.backups.show', $backup)
                ->with('success', 'Sauvegarde '.$backup->code.' créée avec succès.');
        } catch (Throwable $e) {
            return back()->withErrors(['backup' => $e->getMessage()]);
        }
    }

    public function updatePolicy(Request $request): RedirectResponse
    {
        $this->authorizeUpdate();
        $company = Workspace::company();
        abort_unless($company, 403);

        $data = $request->validate([
            'auto_backup' => ['nullable', 'boolean'],
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'retention_days' => ['required', 'integer', 'min:1', 'max:365'],
            'include_files' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $data['auto_backup'] = $request->boolean('auto_backup');
        $data['include_files'] = $request->boolean('include_files');

        $this->backups->savePolicy($data, $company);

        return back()->with('success', 'Planification des sauvegardes enregistrée.');
    }

    public function showBackup(SystemBackup $backup): View
    {
        $this->authorizeView();
        $company = Workspace::company();
        abort_unless($company && (int) $backup->company_id === (int) $company->id, 404);

        $preview = $this->backups->preview($backup);

        return view('system.backup-show', [
            'company' => $company,
            'backup' => $backup,
            'manifest' => $preview['manifest'],
            'exists' => $preview['exists'],
        ]);
    }

    public function restoreForm(SystemBackup $backup): View
    {
        $this->authorizeUpdate();
        $company = Workspace::company();
        abort_unless($company && (int) $backup->company_id === (int) $company->id, 404);

        $preview = $this->backups->preview($backup);

        return view('system.restore', [
            'company' => $company,
            'backup' => $backup,
            'manifest' => $preview['manifest'],
            'exists' => $preview['exists'],
        ]);
    }

    public function restore(Request $request, SystemBackup $backup): RedirectResponse
    {
        $this->authorizeUpdate();
        $company = Workspace::company();
        abort_unless($company && (int) $backup->company_id === (int) $company->id, 404);

        $data = $request->validate([
            'confirmation' => ['required', 'string'],
            'acknowledge' => ['accepted'],
        ]);

        try {
            $this->backups->restore($backup, $request->user(), $data['confirmation']);

            return redirect()
                ->route('system.backups.show', $backup)
                ->with('success', 'Restauration complète terminée. Vérifiez vos données.');
        } catch (Throwable $e) {
            return back()->withErrors(['confirmation' => $e->getMessage()]);
        }
    }

    public function destroyBackup(SystemBackup $backup): RedirectResponse
    {
        $this->authorizeUpdate();
        $company = Workspace::company();
        abort_unless($company && (int) $backup->company_id === (int) $company->id, 404);

        $this->backups->deleteBackup($backup);

        return redirect()->route('system.backups')->with('success', 'Sauvegarde supprimée.');
    }

    public function alerts(): View
    {
        $this->authorizeView();
        $company = Workspace::company();
        abort_unless($company, 403);

        $alerts = SystemAlert::query()
            ->where(fn ($q) => $q->where('company_id', $company->id)->orWhereNull('company_id'))
            ->latest()
            ->paginate(30);

        return view('system.alerts', compact('company', 'alerts'));
    }

    public function resolveAlert(SystemAlert $alert): RedirectResponse
    {
        $this->authorizeUpdate();
        $this->health->resolveAlert($alert);

        return back()->with('success', 'Alerte marquée comme résolue.');
    }

    public function journal(Request $request): View
    {
        $this->authorizeView();
        $company = Workspace::company();
        abort_unless($company, 403);

        $category = $request->query('category');
        if ($category && ! in_array($category, ['backup', 'restore', 'error', 'incident', 'health'], true)) {
            $category = null;
        }

        $events = $this->health->journal($company, $category, 100);

        return view('system.journal', compact('company', 'events', 'category'));
    }

    public function refreshHealth(): RedirectResponse
    {
        $this->authorizeView();
        $company = Workspace::company();
        abort_unless($company, 403);
        $this->health->check($company, true);

        return back()->with('success', 'Diagnostic système actualisé.');
    }

    private function authorizeView(): void
    {
        $this->authorize('settings.view');
    }

    private function authorizeUpdate(): void
    {
        $this->authorize('settings.update');
    }
}
