<?php

namespace App\Services;

class MoneyService
{
    // 1) Sayıyı TR formatında (1.234,56) ve para birimiyle birlikte yazar.
    public static function fmtMoney(float $v, string $currency): string
    {
        $currency = strtoupper(trim($currency ?: 'TRY'));
        return number_format($v, 2, ',', '.') . ' ' . $currency;
    }

    // 2) TRY tutarı yazıyla (Lira + Kuruş) yazar. Örn: 1234.56 → BİN İKİ YÜZ OTUZ DÖRT TÜRK LİRASI ELLİ ALTI KURUŞ
    public static function toWordsTRY(float $v): string
    {
        $v = round($v, 2); // kuruş hassasiyeti
        $lira = (int)floor($v + 0.00001);
        $kurus = (int)round(($v - $lira) * 100);

        $ones = ['', 'BİR', 'İKİ', 'ÜÇ', 'DÖRT', 'BEŞ', 'ALTI', 'YEDİ', 'SEKİZ', 'DOKUZ'];
        $tens = ['', 'ON', 'YİRMİ', 'OTUZ', 'KIRK', 'ELLİ', 'ALTMIŞ', 'YETMİŞ', 'SEKSEN', 'DOKSAN'];
        $thousands = ['', 'BİN', 'MİLYON', 'MİLYAR', 'TRİLYON', 'KATRİLYON'];

        $chunkToWords = function (int $n) use ($ones, $tens): string {
            $n = $n % 1000;
            if ($n === 0) return '';
            $h = intdiv($n, 100);
            $t = intdiv($n % 100, 10);
            $o = $n % 10;
            $parts = [];
            if ($h > 0) {
                // 100: YÜZ, 200: İKİ YÜZ
                $parts[] = $h === 1 ? 'YÜZ' : $ones[$h] . ' YÜZ';
            }
            if ($t > 0) {
                $parts[] = $tens[$t];
            }
            if ($o > 0) {
                $parts[] = $ones[$o];
            }
            return trim(implode(' ', $parts));
        };

        // Lira bölümü
        if ($lira === 0) {
            $liraText = 'SIFIR';
        } else {
            $parts = [];
            $i = 0;
            while ($lira > 0) {
                $n = $lira % 1000;
                if ($n > 0) {
                    $chunk = $chunkToWords($n);
                    // "BİN" kuralı: 1000–1999 arası en yüksek chunk 1 ise "BİR BİN" yerine "BİN"
                    if ($i === 1 && $n === 1) {
                        $parts[] = 'BİN';
                    } else {
                        $parts[] = trim(($chunk !== '' ? $chunk . ' ' : '') . $thousands[$i]);
                    }
                }
                $lira = intdiv($lira, 1000);
                $i++;
            }
            $parts = array_reverse($parts);
            $liraText = trim(implode(' ', $parts));
        }

        $text = $liraText . ' TÜRK LİRASI';

        // Kuruş bölümü
        if ($kurus > 0) {
            $kText = '';
            $t = intdiv($kurus, 10);
            $o = $kurus % 10;
            if ($t > 0) $kText .= $tens[$t];
            if ($o > 0) $kText .= ($kText ? ' ' : '') . $ones[$o];
            if ($kText === '') $kText = 'SIFIR';
            $text .= ' ' . $kText . ' KURUŞ';
        }

        return $text;
    }

    // 3) Ödeme planı özeti. Girdi örneği:
    //   [
    //     ['method'=>'NAKIT','currency'=>'TRY','tutar'=>1000,'cek_tarihi'=>null],
    //     ['method'=>'CEK','currency'=>'TRY','tutar'=>2000,'cek_tarihi'=>'2025-01-10'],
    //     ['method'=>'BARTER','currency'=>'USD','tutar'=>500,'cek_tarihi'=>null],
    //   ]
    public static function summarizePayments(array $items): array
    {
        $lines = [];
        $sumByMethod = ['CEK' => 0.0, 'NAKIT' => 0.0, 'BARTER' => 0.0];
        $totalTRY = 0.0;

        foreach ($items as $it) {
            $method = strtoupper(trim((string)($it['method'] ?? '')));
            // Türkçe varyantları normalize et
            if (in_array($method, ['NAKİT', 'CASH'], true)) $method = 'NAKIT';
            if ($method === 'ÇEK') $method = 'CEK';
            if ($method === '') $method = 'BARTER';

            $currency = strtoupper(trim((string)($it['currency'] ?? 'TRY')));
            $amount = (float)($it['tutar'] ?? 0);
            $money = self::fmtMoney($amount, $currency);
            $words = ($currency === 'TRY') ? self::toWordsTRY($amount) : '';

            if ($method === 'NAKIT') {
                $lines[] = "- {$money}" . ($words ? " ({$words})" : '') . " - NAKİT";
                $sumByMethod['NAKIT'] += $amount;
            } elseif ($method === 'CEK') {
                $dateRaw = $it['cek_tarihi'] ?? null;
                $dateStr = '';
                if (!empty($dateRaw)) {
                    $ts = strtotime((string)$dateRaw);
                    if ($ts !== false) $dateStr = date('d.m.Y', $ts) . ' TARİHLİ ÇEK';
                }
                if ($dateStr === '') $dateStr = 'ÇEK';
                $lines[] = "- {$money}" . ($words ? " ({$words})" : '') . " - {$dateStr}";
                $sumByMethod['CEK'] += $amount;
            } else {
                $lines[] = "- {$money}" . ($words ? " ({$words})" : '') . " - BARTER";
                $sumByMethod['BARTER'] += $amount;
            }

            if ($currency === 'TRY') $totalTRY += $amount;
        }

        // Çek toplam satırı (TRY bazında)
        if ($sumByMethod['CEK'] > 0) {
            $sumStr = self::fmtMoney($sumByMethod['CEK'], 'TRY');
            $sumWords = self::toWordsTRY($sumByMethod['CEK']);
            $lines[] = "Olmak üzere toplam {$sumStr} ({$sumWords}) TUTARINDA ÇEK";
        }

        // Genel toplam (TRY)
        $sumTotalStr = self::fmtMoney($totalTRY, 'TRY');
        $sumTotalWords = self::toWordsTRY($totalTRY);
        if (!empty($lines)) $lines[] = '';
        $lines[] = "SÖZLEŞME TOPLAM BEDELİ: {$sumTotalStr} ({$sumTotalWords}).";

        return [
            'text' => implode("\n\n", $lines),
            'total_try' => $totalTRY
        ];
    }

    // 4) Payment plan formatted as table (Tür, Vade, Tutar, Para Birimi)
    public static function formatPaymentTable(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        // Translate payment methods to Turkish
        $methodMap = [
            'cash' => 'Nakit',
            'CASH' => 'Nakit',
            'NAKİT' => 'Nakit',
            'NAKIT' => 'Nakit',
            'cheque' => 'Çek',
            'CEK' => 'Çek',
            'ÇEK' => 'Çek',
            'CHEQUE' => 'Çek',
            'transfer' => 'Havale/EFT',
            'TRANSFER' => 'Havale/EFT',
            'BARTER' => 'Takas',
            'barter' => 'Takas'
        ];

        // Start with title
        $output = "Ödeme Planı:\n";

        // Add header row
        $output .= "Tür | Vade | Tutar | Para Birimi\n";

        // Add each payment row
        foreach ($items as $item) {
            $method = $item['method'] ?? $item['type'] ?? 'cash';
            $methodTr = $methodMap[$method] ?? ucfirst($method);

            $dueDate = $item['cek_tarihi'] ?? $item['due_date'] ?? '';
            if (!empty($dueDate)) {
                $ts = strtotime((string)$dueDate);
                if ($ts !== false) {
                    $dueDate = date('d.m.Y', $ts);
                }
            }

            $amount = (float)($item['tutar'] ?? $item['amount'] ?? 0);
            $currency = strtoupper(trim((string)($item['currency'] ?? 'TRY')));
            $formattedAmount = number_format($amount, 2, ',', '.');

            $output .= $methodTr . " | " . $dueDate . " | " . $formattedAmount . " | " . $currency . "\n";
        }

        return trim($output);
    }
}
