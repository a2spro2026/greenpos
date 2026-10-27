<?php

namespace App\Support;

class RimalOffline
{
    /** Version du service worker : change à chaque build front (manifest Vite) ou mise à jour de pos-offline.js. */
    public static function version(): string
    {
        $parts = [];
        foreach (['build/manifest.json', 'js/pos-offline.js', 'sw.js'] as $f) {
            $p = public_path($f);
            $parts[] = is_file($p) ? (string) filemtime($p) : '0';
        }

        return substr(md5(implode('|', $parts)), 0, 10);
    }

    public static function swUrl(): string
    {
        return '/sw.js?v='.self::version();
    }
}
