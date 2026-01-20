<?php

declare(strict_types=1);

// Global namespace - these are top-level helper functions
// This file is required separately to make these functions available globally

/**
 * Translate a message
 * @param string $key Translation key (file.key format)
 * @param array $params Replacement parameters
 * @return string Translated message
 */
function trans(string $key, array $params = []): string
{
    return \App\Language\LanguageManager::get($key, $params);
}

/**
 * Get current language
 */
function currentLanguage(): string
{
    return \App\Language\LanguageManager::getCurrentLanguage();
}

/**
 * Set language
 */
function setLanguage(string $language): void
{
    \App\Language\LanguageManager::setLanguage($language);
}

/**
 * Get all available languages
 */
function getLanguages(): array
{
    return \App\Language\LanguageManager::getAvailableLanguages();
}

/**
 * Check if language is current
 */
function isLanguage(string $language): bool
{
    return currentLanguage() === $language;
}
