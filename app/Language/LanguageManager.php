<?php

declare(strict_types=1);

namespace App\Language;

class LanguageManager
{
    private static string $currentLanguage = 'tr'; // Default Turkish
    private static array $translations = [];
    private static string $languagePath = '';

    /**
     * Initialize language manager
     */
    public static function init(string $basePath): void
    {
        self::$languagePath = $basePath . '/app/Language';

        // Get language from session, cookie, or default
        self::$currentLanguage = self::detectLanguage();

        // Load translations for current language
        self::loadLanguage(self::$currentLanguage);
    }

    /**
     * Detect language from session/cookie/default
     */
    private static function detectLanguage(): string
    {
        // Check session first
        if (isset($_SESSION['language']) && in_array($_SESSION['language'], ['en', 'tr'])) {
            return $_SESSION['language'];
        }

        // Check cookie
        if (isset($_COOKIE['language']) && in_array($_COOKIE['language'], ['en', 'tr'])) {
            $lang = $_COOKIE['language'];
            $_SESSION['language'] = $lang;
            return $lang;
        }

        // Check browser language
        $browserLang = self::getBrowserLanguage();
        if ($browserLang) {
            return $browserLang;
        }

        // Default to Turkish
        return 'tr';
    }

    /**
     * Get language from browser Accept-Language header
     */
    private static function getBrowserLanguage(): ?string
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $preferred = explode('-', $languages[0]);
        $lang = strtolower(trim($preferred[0]));

        return in_array($lang, ['en', 'tr']) ? $lang : null;
    }

    /**
     * Load language files
     */
    private static function loadLanguage(string $language): void
    {
        $language = in_array($language, ['en', 'tr']) ? $language : 'tr';
        $languageDir = self::$languagePath . '/' . $language;

        if (!is_dir($languageDir)) {
            throw new \Exception("Language directory not found: {$languageDir}");
        }

        // Load all PHP files in language directory
        foreach (glob($languageDir . '/*.php') as $file) {
            $key = basename($file, '.php');
            self::$translations[$key] = include $file;
        }
    }

    /**
     * Get translation
     * Usage: trans('messages.welcome') or trans('validation.required')
     */
    public static function get(string $key, array $params = []): string
    {
        $parts = explode('.', $key);

        if (count($parts) !== 2) {
            return $key; // Return key if invalid format
        }

        [$file, $key] = $parts;

        if (!isset(self::$translations[$file][$key])) {
            return $key; // Return key if translation not found
        }

        $text = self::$translations[$file][$key];

        // Replace parameters
        foreach ($params as $param => $value) {
            $text = str_replace(":{$param}", $value, $text);
        }

        return $text;
    }

    /**
     * Set current language
     */
    public static function setLanguage(string $language): void
    {
        if (!in_array($language, ['en', 'tr'])) {
            return;
        }

        self::$currentLanguage = $language;
        $_SESSION['language'] = $language;

        // Set cookie for 1 year
        setcookie('language', $language, time() + (365 * 24 * 60 * 60), '/');

        // Reload translations
        self::$translations = [];
        self::loadLanguage($language);
    }

    /**
     * Get current language
     */
    public static function getCurrentLanguage(): string
    {
        return self::$currentLanguage;
    }

    /**
     * Get available languages
     */
    public static function getAvailableLanguages(): array
    {
        return ['en' => 'English', 'tr' => 'Türkçe'];
    }
}
