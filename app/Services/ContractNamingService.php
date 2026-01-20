<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Contract naming service for auto-generating contract titles
 */
class ContractNamingService
{
    /**
     * Generate contract title in format: SZL_PROJECT_SUBJECT_CONTRACTOR_YYYYMMDD
     * 
     * @param string $projectName Project name (max 6 chars: 3 from each word)
     * @param string $subject Contract subject (first 8 chars)
     * @param string $subcontractorName Subcontractor company name (first 8 chars)
     * @param string $contractDate Contract date in YYYY-MM-DD or YYYYMMDD format
     * @return string Generated contract title
     * @example generateTitle('TOPCULAR LUVIYA', '3BOYUTLU TASARIMI', 'ARTIEKSI LTD', '2024-09-19')
     *          Returns: SZL_TOPLUV_3BOYUTLU_ARTIEKSI_20240919
     */
    public static function generateTitle(
        string $projectName,
        string $subject = '',
        string $subcontractorName = '',
        string $contractDate = ''
    ): string {
        // Extract project code (6 characters: 3 from each word)
        $prj = self::extractProjectCode($projectName);

        // Extract subject (first 8 characters)
        $subj = self::extractCode($subject, 8);

        // Extract contractor (first 8 characters)
        $cont = self::extractCode($subcontractorName, 8);

        // Format date as YYYYMMDD
        $date = self::formatDateForTitle($contractDate);

        return "SZL_{$prj}_{$subj}_{$cont}_{$date}";
    }

    /**
     * Extract project code (6 characters: 3 from each word)
     * Example: "TOPCULAR LUVIYA" -> "TOPLUV"
     */
    private static function extractProjectCode(string $projectName): string
    {
        if (empty($projectName)) {
            return 'XXXXXX';
        }

        // Sanitize
        $projectName = mb_strtoupper($projectName, 'UTF-8');
        $projectName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $projectName);
        $projectName = preg_replace('/[^A-Z0-9\s]/', '', $projectName);
        $projectName = trim($projectName);

        if (empty($projectName)) {
            return 'XXXXXX';
        }

        // Split by spaces
        $words = preg_split('/\s+/', $projectName, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) {
            return 'XXXXXX';
        }

        // Single word: take first 6 characters
        if (count($words) === 1) {
            return str_pad(mb_substr($words[0], 0, 6, 'UTF-8'), 6, 'X');
        }

        // Multiple words: 3 chars from first word + 3 chars from second word
        $code = mb_substr($words[0], 0, 3, 'UTF-8');
        $code .= mb_substr($words[1], 0, 3, 'UTF-8');
        return str_pad($code, 6, 'X');
    }

    /**
     * Extract and sanitize code from text
     * @param string $text Source text
     * @param int $length Number of characters to extract
     * @return string Sanitized and truncated text (uppercase, alphanumeric only)
     */
    private static function extractCode(string $text, int $length): string
    {
        if (empty($text)) {
            return str_repeat('X', $length);
        }

        // Sanitize: uppercase and remove non-alphanumeric
        $text = mb_strtoupper($text, 'UTF-8');
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('/[^A-Z0-9]/', '', $text);
        $text = trim($text);

        // Return first N characters, pad with X if too short
        if (empty($text)) {
            return str_repeat('X', $length);
        }

        return str_pad(mb_substr($text, 0, $length, 'UTF-8'), $length, 'X');
    }

    /**
     * Format date as YYYYMMDD for contract title
     * Accepts YYYY-MM-DD, DD-MM-YYYY, YYYYMMDD formats
     */
    private static function formatDateForTitle(string $contractDate): string
    {
        if (empty($contractDate)) {
            return '00000000';
        }

        try {
            // Try to parse the date
            $date = \DateTime::createFromFormat('Y-m-d', $contractDate)
                ?: \DateTime::createFromFormat('d-m-Y', $contractDate)
                ?: \DateTime::createFromFormat('Ymd', $contractDate)
                ?: new \DateTime($contractDate);

            return $date->format('Ymd');
        } catch (\Exception $e) {
            return '00000000';
        }
    }

    /**
     * Valid contract statuses
     */
    public static function validStatuses(): array
    {
        return ['PREPARING', 'SIGNED', 'ARCHIVED', 'CANCELLED'];
    }

    /**
     * Get status label in Turkish
     */
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'PREPARING' => 'Hazırlık Aşamasında',
            'SIGNED' => 'İmzalı',
            'ARCHIVED' => 'Arşivlenmiş',
            'CANCELLED' => 'İptal Edilmiş',
            default => $status
        };
    }
}
