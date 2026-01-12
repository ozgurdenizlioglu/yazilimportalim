<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Contract naming service for auto-generating contract titles
 */
class ContractNamingService
{
    /**
     * Generate contract title in format: SZL_PRJ_SUBJ_SUBC_YYYYMMDD
     * 
     * @param string $projectShortName 3-char project short name (or full name if short name is empty)
     * @param string $subject First 8 chars of subject
     * @param string $subcontractorName First 8 chars of contractor company name
     * @param string $contractDate Date in Y-m-d format
     * @return string Generated contract title
     */
    public static function generateTitle(
        string $projectShortName,
        string $subject,
        string $subcontractorName,
        string $contractDate
    ): string {
        // Use short_name if provided (should be 3 chars), otherwise extract first 3 chars from full name
        $prj = self::sanitizeAndTruncate($projectShortName, 3);

        // Extract first 8 chars from subject (uppercase, sanitized)
        $subj = self::sanitizeAndTruncate($subject, 8);

        // Extract first 8 chars from subcontractor name (uppercase, sanitized)
        $subc = self::sanitizeAndTruncate($subcontractorName, 8);

        // Format date as YYYYMMDD
        try {
            $date = \DateTime::createFromFormat('Y-m-d', $contractDate);
            if ($date === false) {
                $date = new \DateTime($contractDate);
            }
            $dateStr = $date->format('Ymd');
        } catch (\Throwable $e) {
            $dateStr = (new \DateTime())->format('Ymd');
        }

        return "SZL_{$prj}_{$subj}_{$subc}_{$dateStr}";
    }

    /**
     * Sanitize string and extract first N characters
     * Removes non-ASCII characters and converts to uppercase
     */
    private static function sanitizeAndTruncate(string $str, int $length): string
    {
        // Convert to uppercase
        $str = mb_strtoupper($str, 'UTF-8');

        // Remove non-ASCII characters and keep only alphanumeric
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $str = preg_replace('/[^A-Z0-9]/', '', $str);

        // Truncate to desired length
        return mb_substr($str, 0, $length, 'UTF-8');
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
