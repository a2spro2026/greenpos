<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class SessionManager
{
    public const LOCK_KEY = 'session_locked';

    public const LOCK_AT_KEY = 'session_locked_at';

    public const LOCK_USER_KEY = 'session_locked_user_id';

    public const INTENDED_KEY = 'url.intended';

    public static function isLocked(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        return (bool) session(self::LOCK_KEY)
            && (int) session(self::LOCK_USER_KEY) === (int) Auth::id();
    }

    public static function lock(): void
    {
        if (! Auth::check()) {
            return;
        }

        session([
            self::LOCK_KEY => true,
            self::LOCK_AT_KEY => now()->toIso8601String(),
            self::LOCK_USER_KEY => Auth::id(),
        ]);
    }

    public static function unlock(): void
    {
        session()->forget([self::LOCK_KEY, self::LOCK_AT_KEY, self::LOCK_USER_KEY]);
    }

    public static function rememberLastAccount(User $user): void
    {
        $payload = [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->displayName(),
            'initials' => $user->initials(),
            'photo' => $user->photoUrl(),
        ];

        Cookie::queue(
            cookie(
                config('greenpos.last_account_cookie'),
                encrypt(json_encode($payload)),
                60 * 24 * (int) config('greenpos.last_account_days', 30),
                '/',
                null,
                config('session.secure'),
                true,
                false,
                config('session.same_site', 'lax')
            )
        );
    }

    public static function lastAccount(?Request $request = null): ?array
    {
        $request ??= request();
        $raw = $request->cookie(config('greenpos.last_account_cookie'));
        if (! $raw) {
            return null;
        }

        try {
            $data = json_decode(decrypt($raw), true);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($data) || empty($data['email'])) {
            return null;
        }

        return $data;
    }

    public static function forgetLastAccount(): SymfonyCookie
    {
        return Cookie::forget(config('greenpos.last_account_cookie'));
    }

    public static function blockAutoLogin(): void
    {
        Cookie::queue(
            cookie(
                config('greenpos.auto_login_block_cookie'),
                '1',
                60 * 24 * 30,
                '/',
                null,
                config('session.secure'),
                true,
                false,
                config('session.same_site', 'lax')
            )
        );
    }

    public static function clearAutoLoginBlock(): void
    {
        Cookie::queue(Cookie::forget(config('greenpos.auto_login_block_cookie')));
    }

    public static function autoLoginAllowed(Request $request): bool
    {
        if (! config('greenpos.auto_login', true)) {
            return false;
        }

        if (! app()->environment('local', 'testing')) {
            return false;
        }

        return ! $request->cookie(config('greenpos.auto_login_block_cookie'));
    }

    /**
     * Full logout: clear auth, tokens, workspace, lock, regenerate session.
     */
    public static function logout(Request $request): void
    {
        $user = Auth::user();

        if ($user) {
            self::rememberLastAccount($user);
            $user->forceFill(['remember_token' => null])->save();
        }

        Auth::logout();

        self::blockAutoLogin();
        self::unlock();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public static function loginUser(User $user, Request $request, bool $remember = false): void
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        self::unlock();
        self::clearAutoLoginBlock();
        self::rememberLastAccount($user);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_device' => substr((string) $request->userAgent(), 0, 255),
        ])->save();
    }
}
