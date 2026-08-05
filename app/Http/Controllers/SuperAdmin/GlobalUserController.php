<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlobalUserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term);
                });
            })
            ->when($request->boolean('admins_only'), fn ($q) => $q->where('is_platform_admin', true))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('superadmin.users.index', [
            'users' => $users,
            'filters' => $request->only(['q', 'admins_only']),
        ]);
    }

    public function toggleAdmin(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas retirer votre propre accès.');
        }

        $user->update(['is_platform_admin' => ! $user->is_platform_admin]);

        return back()->with('success', 'Droits Super Admin mis à jour.');
    }
}
