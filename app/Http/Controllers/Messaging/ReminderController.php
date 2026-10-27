<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use App\Services\ReminderService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReminderController extends Controller
{
    public function __construct(private ReminderService $reminders)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('notifications.view');
        $company = Workspace::company();

        $items = Reminder::query()
            ->forCompany($company->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->string('channel')))
            ->latest('scheduled_at')
            ->paginate(25)
            ->withQueryString();

        return view('messaging.reminders.index', [
            'items' => $items,
            'channels' => Reminder::CHANNELS,
            'statuses' => Reminder::STATUSES,
            'filters' => $request->only(['status', 'channel']),
            'pending' => Reminder::query()->forCompany($company->id)->where('status', 'pending')->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('notifications.create');

        return view('messaging.reminders.create', [
            'channels' => Reminder::CHANNELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('notifications.create');
        $data = $request->validate([
            'channel' => ['required', 'in:email,sms,whatsapp,notification'],
            'recipient' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $this->reminders->schedule($data);

        return redirect()->route('messaging.reminders.index')->with('success', 'Rappel planifié.');
    }

    public function send(Reminder $reminder): RedirectResponse
    {
        $this->authorize('notifications.update');
        abort_unless($reminder->company_id === Workspace::company()->id, 404);
        $ok = $this->reminders->send($reminder);

        return back()->with($ok ? 'success' : 'warning', $ok ? 'Rappel envoyé.' : 'Envoi échoué (vérifiez la gateway).');
    }

    public function markSent(Reminder $reminder): RedirectResponse
    {
        $this->authorize('notifications.update');
        abort_unless($reminder->company_id === Workspace::company()->id, 404);
        $this->reminders->markSent($reminder);

        return back()->with('success', 'Marqué comme envoyé.');
    }

    public function cancel(Reminder $reminder): RedirectResponse
    {
        $this->authorize('notifications.update');
        abort_unless($reminder->company_id === Workspace::company()->id, 404);
        $this->reminders->cancel($reminder);

        return back()->with('success', 'Rappel annulé.');
    }

    public function processDue(): RedirectResponse
    {
        $this->authorize('notifications.update');
        $n = $this->reminders->processDue();

        return back()->with('success', "{$n} rappel(s) traités.");
    }
}
