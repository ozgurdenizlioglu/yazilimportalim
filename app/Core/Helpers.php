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

    /**
     * Convert Turkish characters to ASCII uppercase
     * Example: "İnşaat İşleri" → "INSAAT ISLERI"
     */
    public static function toAsciiUppercase(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $turkish = ['ç', 'ğ', 'ı', 'İ', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'Ö', 'Ş', 'Ü'];
        $ascii = ['C', 'G', 'I', 'I', 'O', 'S', 'U', 'C', 'G', 'O', 'S', 'U'];

        $value = str_replace($turkish, $ascii, $value);
        return mb_strtoupper($value, 'UTF-8');
    }
}

// Global helper functions are in HelpersFunctions.php
// They are automatically loaded in public/index.php
