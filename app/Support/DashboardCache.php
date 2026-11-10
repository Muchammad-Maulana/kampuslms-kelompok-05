<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

final class DashboardCache
{
    private const VERSION_KEY = 'dashboard.stats.version';

    /**
     * @param  callable(): array<int, array{label: string, value: int}>  $resolver
     * @return array<int, array{label: string, value: int}>
     */
    public static function remember(User $user, callable $resolver): array
    {
        return Cache::remember(
            self::key($user),
            now()->addMinutes(10),
            $resolver
        );
    }

    public static function invalidate(): void
    {
        Cache::increment(self::VERSION_KEY);
    }

    private static function key(User $user): string
    {
        $version = Cache::rememberForever(self::VERSION_KEY, fn (): int => 1);

        return "dashboard.stats.{$version}.{$user->getKey()}";
    }
}
