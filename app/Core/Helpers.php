<?php
declare(strict_types=1);

namespace App\Core;

final class Helpers
{
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function url(string $path = '/'): string
    {
        $base = rtrim($_ENV['APP_URL'] ?? '', '/');
        $path = '/' . ltrim($path, '/');
        return $base ? ($base . $path) : $path;
    }
}
