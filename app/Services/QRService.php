<?php

declare(strict_types=1);

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

final class QRService
{
    /**
     * Verilen metinden geçerli bir PNG QR üretir ve savePath'e yazar (atomik).
     *
     * @param string $text     QR içeriği
     * @param string $savePath Yazılacak PNG dosya yolu
     * @param array{
     *     scale?: int,
     *     margin?: int,
     *     eccLevel?: string,   // QRCode::ECC_L|M|Q|H
     *     version?: int|null,  // 1..40 veya null (auto)
     *     outputBase64?: bool, // true ise dosyaya yazmak yerine base64 döndür
     * } $options
     *
     * @return string|null outputBase64=true ise data URI döner, aksi halde null
     * @throws \RuntimeException
     */
    public static function make(string $text, string $savePath, array $options = []): ?string
    {
        $scale       = isset($options['scale']) ? (int)$options['scale'] : 6;
        $margin      = isset($options['margin']) ? (int)$options['margin'] : 2;
        $eccLevel    = $options['eccLevel'] ?? QRCode::ECC_L;
        $version     = $options['version'] ?? null;
        $outputBase64 = (bool)($options['outputBase64'] ?? false);

        if ($text === '') {
            throw new \RuntimeException('QR içeriği boş olamaz.');
        }

        $dir = dirname($savePath);
        if ($dir === '' || $dir === '.' || $dir === DIRECTORY_SEPARATOR) {
            throw new \RuntimeException('QR kaydetme yolu geçersiz: ' . $savePath);
        }
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException('QR klasörü oluşturulamadı: ' . $dir);
            }
        }
        if (!is_writable($dir)) {
            throw new \RuntimeException('QR klasörüne yazma izni yok: ' . $dir);
        }

        $qrOptsArray = [
            'outputType'  => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'    => $eccLevel, // ECC_L, ECC_M, ECC_Q, ECC_H
            'scale'       => max(1, $scale),
            'margin'      => max(0, $margin),
            'imageBase64' => false,     // dosya veya ham png string
        ];

        if ($version !== null) {
            $qrOptsArray['version'] = max(1, min(40, (int)$version));
        }

        $qrOpts = new QROptions($qrOptsArray);

        $pngData = (new QRCode($qrOpts))->render($text);
        if (!is_string($pngData) || $pngData === '') {
            throw new \RuntimeException('QR üretimi başarısız (boş çıktı).');
        }

        if ($outputBase64) {
            // Doğrulama (temel)
            if (strlen($pngData) < 100) {
                throw new \RuntimeException('QR PNG verisi olağandışı küçük.');
            }
            return 'data:image/png;base64,' . base64_encode($pngData);
        }

        // Atomik yazım: önce .part dosyasına yaz, sonra rename
        $tmpPath = $savePath . '.part_' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmpPath, $pngData) === false) {
            @unlink($tmpPath);
            throw new \RuntimeException('QR geçici dosyaya yazılamadı: ' . $tmpPath);
        }

        // Hızlı doğrulama (geçici dosyada)
        clearstatcache(true, $tmpPath);
        if (!is_file($tmpPath) || (filesize($tmpPath) ?: 0) < 100) {
            @unlink($tmpPath);
            throw new \RuntimeException('QR geçici dosya beklenen boyutta değil: ' . $tmpPath);
        }
        $info = @getimagesize($tmpPath);
        if ($info === false || ($info['mime'] ?? '') !== 'image/png') {
            @unlink($tmpPath);
            throw new \RuntimeException('QR geçici dosya PNG doğrulamadan geçemedi: ' . $tmpPath);
        }

        // rename ile atomik olarak hedefe taşı
        if (!@rename($tmpPath, $savePath)) {
            // Bazı FS’lerde rename cross-device sorun çıkartabilir; fallback kopyala+sİl
            if (!@copy($tmpPath, $savePath)) {
                @unlink($tmpPath);
                throw new \RuntimeException('QR dosyası hedefe taşınamadı/kopyalanamadı: ' . $savePath);
            }
            @unlink($tmpPath);
        }

        return null;
    }

    /**
     * Basit helper: aynı parametrelerle data URI döndürür.
     *
     * @return string data:image/png;base64,...
     */
    public static function makeBase64(string $text, array $options = []): string
    {
        $opts = $options;
        $opts['outputBase64'] = true;
        // Dosya gerekmediği için geçici path uyduruyoruz; make() outputBase64=true olduğunda dosya yazmaz.
        return self::make($text, sys_get_temp_dir() . '/qr_' . bin2hex(random_bytes(4)) . '.png', $opts) ?? '';
    }
}
