<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('notifications.view');
        $this->notifications->seedDemo();
        $stats = $this->notifications->dashboardStats();
        $recent = $this->notifications->latest(10);
        $critical = $this->notifications->queryForCurrentUser()
            ->where(function ($q) {
                $q->where('type', 'critical')->orWhere('priority', 'critical');
            })
            ->where('status', '!=', 'archived')
            ->latest()
            ->limit(5)
            ->get();

        return view('notifications.dashboard', compact('stats', 'recent', 'critical'));
    }

    public function index(Request $request): View
    {
        $this->authorize('notifications.view');

        $sort = $request->string('sort', 'created_at')->toString();
        $dir = $request->string('dir', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        if (! in_array($sort, ['created_at', 'title', 'type', 'priority', 'status'], true)) {
            $sort = 'created_at';
        }

        $notifications = $this->notifications->queryForCurrentUser()
            ->search($request->string('q')->toString())
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->string('priority')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'types' => AppNotification::TYPES,
            'priorities' => AppNotification::PRIORITIES,
            'statuses' => AppNotification::STATUSES,
            'categories' => AppNotification::CATEGORIES,
            'filters' => $request->only(['q', 'type', 'status', 'priority', 'category', 'sort', 'dir']),
        ]);
    }

    public function show(AppNotification $notification): View
    {
        $this->authorize('notifications.view');
        $this->notifications->assertAccess($notification);
        if ($notification->isUnread()) {
            $this->notifications->markRead($notification);
            $notification->refresh();
        }

        return view('notifications.show', compact('notification'));
    }

    public function markRead(AppNotification $notification): RedirectResponse
    {
        $this->authorize('notifications.update');
        $this->notifications->markRead($notification);

        return back()->with('success', 'Notification marquée comme lue.');
    }

    public function markAllRead(): RedirectResponse
    {
        $this->authorize('notifications.update');
        $count = $this->notifications->markAllRead();

        return back()->with('success', "{$count} notification(s) marquée(s) comme lues.");
    }

    public function archive(AppNotification $notification): RedirectResponse
    {
        $this->authorize('notifications.archive');
        $this->notifications->archive($notification);

        return back()->with('success', 'Notification archivée.');
    }

    public function destroy(AppNotification $notification): RedirectResponse
    {
        $this->authorize('notifications.delete');
        $this->notifications->delete($notification);

        return back()->with('success', 'Notification supprimée.');
    }

    public function preferences(): View
    {
        $this->authorize('notifications.preferences');
        $preferences = $this->notifications->preferencesFor();

        return view('notifications.preferences', [
            'preferences' => $preferences,
            'types' => AppNotification::TYPES,
            'categories' => AppNotification::CATEGORIES,
        ]);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $this->authorize('notifications.preferences');

        $data = [
            'enabled' => $request->boolean('enabled'),
            'frequency' => $request->string('frequency')->toString() ?: 'realtime',
            'types' => $request->input('types', []),
            'categories' => $request->input('categories', []),
            'channels' => [
                'internal' => $request->boolean('channels.internal'),
                'email' => $request->boolean('channels.email'),
                'sms' => $request->boolean('channels.sms'),
                'whatsapp' => $request->boolean('channels.whatsapp'),
                'push' => $request->boolean('channels.push'),
            ],
        ];

        $this->notifications->savePreferences($data);

        return back()->with('success', 'Préférences enregistrées.');
    }
}
