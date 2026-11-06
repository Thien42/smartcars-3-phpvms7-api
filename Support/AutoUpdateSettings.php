<?php

namespace Modules\SmartCARS3phpVMS7Api\Support;

use App\Repositories\SettingRepository;

/**
 * The auto-update settings, read through SettingRepository instead of setting(): setting() caches
 * values for a day in production, which would keep a switched-off updater running and compare
 * against a stale installed commit. (The repository itself only caches when phpVMS's CACHE_ENABLED
 * is on, off by default.)
 */
class AutoUpdateSettings
{
    public const ENABLED = 'smartcars.auto_update';
    public const SECRET = 'smartcars.auto_update_secret';
    public const COMMIT = 'smartcars.auto_update_commit';
    public const STATUS = 'smartcars.auto_update_status';

    public static function isEnabled(): bool
    {
        return self::read(self::ENABLED, false) === true;
    }

    public static function secret(): string
    {
        return (string) self::read(self::SECRET, '');
    }

    public static function installedCommit(): string
    {
        return (string) self::read(self::COMMIT, '');
    }

    public static function saveCommit(string $sha): void
    {
        app(SettingRepository::class)->store(self::COMMIT, $sha);
    }

    public static function saveStatus(string $sha, string $outcome): void
    {
        app(SettingRepository::class)->store(self::STATUS, now()->toIso8601String().' '.$sha.' '.$outcome);
    }

    private static function read(string $key, mixed $default): mixed
    {
        try {
            return app(SettingRepository::class)->retrieve($key);
        } catch (\Throwable $e) {
            // The settings migration has not run yet: the updater stays off.
            return $default;
        }
    }
}
