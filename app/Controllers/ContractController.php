<?php

declare(strict_types=1);

// FILE: app/Controllers/ContractController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Contract;
use App\Services\DocxService;
use App\Services\QRService;
use App\Services\ContractNamingService;
use PDOException;

class ContractController extends Controller
{
    private array $cfg;

    public function __construct()
    {
        // parent::__construct();  // KALDIRILDI: Base Controller'da constructor yoksa "Cannot call constructor" hatası verir.
        // Uygulama config’in varsa burayı ona göre düzenle
        $baseStorage = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage';
        $this->cfg = [
            'paths' => [
                'templates' => $baseStorage . DIRECTORY_SEPARATOR . 'templates',
                'output'    => $baseStorage . DIRECTORY_SEPARATOR . 'output',
                'qrcodes'   => $baseStorage . DIRECTORY_SEPARATOR . 'qrcodes',
                'assets'    => $baseStorage . DIRECTORY_SEPARATOR . 'assets',
                'logos'     => $baseStorage . DIRECTORY_SEPARATOR . 'logos',
            ],
        ];
    }

    // Liste
    public function index(): void
    {
        $pdo = Database::pdo();
        $rows = Contract::all($pdo);
        $this->view('contracts/index', [
            'title' => 'Sözleşmeler',
            'contracts' => $rows,
        ], 'layouts/base');
    }

    // Create form
    public function create(): void
    {
        $this->view('contracts/create', [
            'title' => 'Sözleşme Ekle',
        ]);
    }

    // Store (gelişmiş: sözleşme kaydı + docx üretimi + qr + ödeme planı)
    public function store(): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // Girişler
            $subcontractor_id   = (int)($_POST['subcontractor_company_id'] ?? 0);
            $proje_id       = (int)($_POST['project_id'] ?? 0);
            $disiplin_id    = (int)($_POST['discipline_id'] ?? 0);
            $alt_disiplin_id = (int)($_POST['branch_id'] ?? 0);
            // Note: contract_title is no longer taken from input, it will be generated dynamically
            $konu           = trim((string)($_POST['subject'] ?? ''));
            $tarih          = $_POST['contract_date'] ?? date('Y-m-d');
            $bedelInput     = (string)($_POST['amount'] ?? '0');
            $bedelSanitized = str_replace('.', '', $bedelInput);
            $bedelSanitized = str_replace(',', '.', $bedelSanitized);
            $bedel          = (float)$bedelSanitized;
            $bedel_currency = $_POST['currency_name'] ?? 'TRY';

            // Alan bazlı validasyon
            $errors = [];

            if ($proje_id <= 0) {
                $errors['project_id'] = 'Proje seçimi zorunludur.';
            }

            // Proje geçerliyse işveren firma id'yi DB'den türet (soft delete kontrolleriyle)
            $isveren_id = 0;
            if ($proje_id > 0) {
                $st = $pdo->prepare("
                    SELECT p.company_id
                    FROM public.project p
                    LEFT JOIN public.companies c ON c.id = p.company_id AND c.deleted_at IS NULL
                    WHERE p.id = ? AND p.deleted_at IS NULL
                ");
                $st->execute([$proje_id]);
                $firmaId = (int)($st->fetchColumn() ?: 0);
                if ($firmaId > 0) {
                    $isveren_id = $firmaId;
                } else {
                    $errors['isveren_firma_id'] = 'Seçilen proje veya işveren firması pasif/silinmiş ya da bulunamadı.';
                }
            }

            if ($subcontractor_id <= 0) {
                $errors['subcontractor_company_id'] = 'Yüklenici firma zorunludur.';
            }

            if ($disiplin_id <= 0) {
                $errors['discipline_id'] = 'Disiplin seçimi zorunludur.';
            }

            // Alt disiplin zorunluluğunu koşullu yap
            if ($alt_disiplin_id <= 0) {
                $hasAlt = false;
                if ($disiplin_id > 0) {
                    $q = $pdo->prepare("SELECT 1 FROM public.discipline_branch WHERE discipline_id = ? LIMIT 1");
                    $q->execute([$disiplin_id]);
                    $hasAlt = (bool)$q->fetchColumn();
                }
                if ($hasAlt) {
                    $errors['branch_id'] = 'Alt disiplin seçimi zorunludur.';
                } else {
                    $alt_disiplin_id = 0;
                }
            }

            // Note: contract_title is no longer required as input - it's generated dynamically
            if ($konu === '') {
                $errors['subject'] = 'Konu boş olamaz.';
            }

            // Ek: Yüklenici ve işveren firma soft-delete kontrolü
            if ($subcontractor_id > 0) {
                $chk = $pdo->prepare("SELECT 1 FROM public.companies WHERE id = ? AND deleted_at IS NULL");
                $chk->execute([$subcontractor_id]);
                if (!$chk->fetchColumn()) {
                    $errors['subcontractor_company_id'] = 'Yüklenici firması pasif/silinmiş ya da bulunamadı.';
                }
            }
            if ($isveren_id > 0) {
                $chk = $pdo->prepare("SELECT 1 FROM public.companies WHERE id = ? AND deleted_at IS NULL");
                $chk->execute([$isveren_id]);
                if (!$chk->fetchColumn()) {
                    $errors['isveren_firma_id'] = 'İşveren firması pasif/silinmiş ya da bulunamadı.';
                }
            }

            if (!empty($errors)) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
                $pdo->rollBack();
                return;
            }

            // Firmalar
            $stmt = $pdo->prepare("SELECT * FROM public.companies WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$isveren_id]);
            $isveren   = $stmt->fetch();
            $stmt->execute([$subcontractor_id]);
            $yuklenici = $stmt->fetch();

            // Proje bilgisi
            $stmt = $pdo->prepare("
                SELECT p.*
                FROM public.project p
                WHERE p.id = ? AND p.deleted_at IS NULL
            ");
            $stmt->execute([$proje_id]);
            $proje = $stmt->fetch();
            if (!$proje) {
                throw new \RuntimeException('Proje bulunamadı veya pasif: ID=' . $proje_id);
            }

            // Note: contract_title is NOT stored in database
            // It will be generated dynamically using ContractNamingService when needed

            // Sözleşme kaydı
            $paymentNotes = trim($_POST['payment_notes'] ?? '');
            $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            $stmt = $pdo->prepare("
                INSERT INTO contract
                (contractor_company_id,subcontractor_company_id,contract_date,end_date,subject,project_id,discipline_id,branch_id,currency_code,amount,amount_in_words,payment_notes,created_at,updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())
            ");
            $stmt->execute([
                $isveren_id,
                $subcontractor_id,
                $tarih,
                $endDate,
                $konu,
                $proje_id,
                $disiplin_id,
                $alt_disiplin_id,
                $bedel_currency,
                $bedel,
                \App\Services\MoneyService::toWordsTRY($bedel),
                !empty($paymentNotes) ? $paymentNotes : null
            ]);
            $sozlesme_id = (int)$pdo->lastInsertId();

            // Ödeme planı - Parse JSON from payments_payload
            $payments = [];
            $payloadJson = $_POST['payments_payload'] ?? '[]';

            // Debug: Log the received payload
            error_log('[Contract Store] payments_payload received: ' . $payloadJson);

            $paymentRows = json_decode($payloadJson, true) ?: [];

            // Debug: Log decoded rows
            error_log('[Contract Store] Decoded payment rows: ' . count($paymentRows));

            $stmtIns = $pdo->prepare("
                INSERT INTO payment_plan (contract_id, method, currency, amount, due_date, \"order\")
                VALUES (?,?,?,?,?,?)
            ");

            foreach ($paymentRows as $idx => $row) {
                $method = $row['type'] ?? 'cash';  // type from JS is type, stored as method
                $currencyId = $row['currency_id'] ?? 949;
                $amount = (float)($row['amount'] ?? 0);
                if ($amount <= 0) continue;

                $currencyCode = match ($currencyId) {
                    978 => 'EUR',
                    840 => 'USD',
                    default => 'TRY'
                };

                $dueDate = $row['due_date'] ?? null;
                $stmtIns->execute([$sozlesme_id, $method, $currencyCode, $amount, $dueDate, $idx + 1]);
                $payments[] = ['method' => $method, 'currency' => $currencyCode, 'tutar' => $amount, 'cek_tarihi' => $dueDate];
            }

            $ozet = \App\Services\MoneyService::summarizePayments($payments);

            // Format payment table for template
            $paymentTableFormatted = \App\Services\MoneyService::formatPaymentTable($paymentRows);

            // Append payment notes if they exist
            if (!empty($paymentNotes)) {
                $paymentTableFormatted .= "\n\nÖdemeler ile İlgili Notlar:\n" . $paymentNotes;
            }

            // Generate contract title dynamically for template use
            $projectRef = $proje['short_name'] ?: ($proje['name'] ?? 'PRJ');
            $sozlesme_adi = ContractNamingService::generateTitle(
                $projectRef,
                $konu ?? 'SUBJ',
                $yuklenici['name'] ?? 'SUBC',
                $tarih
            );

            // Değişkenler
            $vars = [
                // Sözleşmenin tarafı olan firma: YÜKLENİCİ
                'SOZLESME_FIRMA_ADI'            => trim($yuklenici['name'] ?? ''),
                'SOZLESME_FIRMA_ADRES'          => trim((($yuklenici['address_line1'] ?? '') . ' ' . ($yuklenici['address_line2'] ?? ''))),
                'SOZLESME_FIRMA_VERGIDAIRESI'   => trim($yuklenici['tax_office'] ?? ''),
                'SOZLESME_FIRMA_VERGINUMARASI'  => trim($yuklenici['tax_number'] ?? ''),
                'SOZLESME_FIRMA_MERSISNO'       => trim($yuklenici['mersis_no'] ?? ''),
                'SOZLESME_FIRMA_TELEFON'        => trim($yuklenici['phone'] ?? ''),
                'SOZLESME_FIRMA_MAIL'           => trim($yuklenici['email'] ?? ''),

                // Projenin sahibi firma: İŞVEREN = PROJE FİRMASI
                'PROJE_ADI'                     => trim($proje['name'] ?? ''),
                'PROJE_FIRMA_ADI'               => trim($isveren['name'] ?? ''),
                'PROJE_FIRMA_ADRES'             => trim((($isveren['address_line1'] ?? '') . ' ' . ($isveren['address_line2'] ?? ''))),
                'PROJE_FIRMA_VERGIDAIRESI'      => trim($isveren['tax_office'] ?? ''),
                'PROJE_FIRMA_VERGINUMARASI'     => trim($isveren['tax_number'] ?? ''),
                'PROJE_FIRMA_MERSISNO'          => trim($isveren['mersis_no'] ?? ''),
                'PROJE_FIRMA_TELEFON'           => trim($isveren['phone'] ?? ''),
                'PROJE_FIRMA_MAIL'              => trim($isveren['email'] ?? ''),
                'PROJE_ADRES'                   => trim($proje['address_line1'] ?? ''),

                'SOZLESME_TARIH'                => date('d.m.Y', strtotime($tarih)),
                'SOZLESME_KONU'                 => $konu,
                'SOZLESME_ADI'                  => $sozlesme_adi,
                'SOZLESME_BEDELI'               => $this->moneyTR($bedel / 100) . ' ' . $bedel_currency,
                'SOZLESME_BEDELI_YAZI'          => \App\Services\MoneyService::toWordsTRY($bedel / 100),
                'ODEME_PLAN_OZETI'              => $paymentTableFormatted ?: $ozet['text'] ?? '',
                'PROJE_GORSEL'                  => $this->safeImagePath($proje['image_url'] ?? null),
            ];

            // Log variables for debugging
            error_log('[Contract Store] Template variables: ' . json_encode(array_map(function ($v) {
                return is_string($v) ? substr($v, 0, 50) : $v;
            }, $vars), JSON_UNESCAPED_UNICODE));

            // Logolar
            $leftLogo = null;   // yüklenici
            $rightLogo = null;  // işveren (proje firması)

            if (!empty($yuklenici['logo_url'])) {
                $leftLogo = $yuklenici['logo_url'];
            }
            if (!empty($isveren['logo_url'])) {
                $rightLogo = $isveren['logo_url'];
            }

            $blankPng = $this->cfg['paths']['assets'] . DIRECTORY_SEPARATOR . 'blank.png';
            $leftLogo  = $this->safeLogoPath($leftLogo, $blankPng);
            $rightLogo = $this->safeLogoPath($rightLogo, $blankPng);

            // Şablon seçimi
            $tplDir = $this->cfg['paths']['templates'];
            $selectedTemplates = $_POST['templates'] ?? [];
            if (is_string($selectedTemplates)) {
                $selectedTemplates = [$selectedTemplates];
            } elseif (!is_array($selectedTemplates)) {
                $selectedTemplates = [];
            }
            if (empty($selectedTemplates)) {
                $selectedTemplates = ['00_Sozlesme_Temp.docx'];
            }

            $valid = [];
            foreach ($selectedTemplates as $tpl) {
                $name = basename($tpl);
                $path = $tplDir . DIRECTORY_SEPARATOR . $name;
                if (is_file($path)) $valid[] = $name;
            }
            if (!$valid) {
                throw new \RuntimeException('Seçilen şablon(lar) bulunamadı. storage/templates klasörünü kontrol edin. Path: ' . $tplDir . ' Files: ' . implode(', ', $selectedTemplates));
            }
            $mainTpl = $valid[0];
            $ekTpls  = array_slice($valid, 1);

            // Final dosya adı
            $slug = $this->slugFileName($sozlesme_adi);
            if ($slug === '') $slug = 'Sozlesme';
            $finalName = $slug . '.docx';
            $finalPath = $this->cfg['paths']['output'] . DIRECTORY_SEPARATOR . $finalName;

            // QR
            $qrPayload = json_encode([
                'type'             => 'contract',
                'sozlesme_id'      => $sozlesme_id,
                'proje_id'         => $proje_id,
                'proje_adi'        => $proje['adi'] ?? '',
                'filename_suggest' => $slug . '.pdf'
            ], JSON_UNESCAPED_UNICODE);

            $qrPath = $this->cfg['paths']['qrcodes'] . DIRECTORY_SEPARATOR . 'qr-' . $sozlesme_id . '.png';
            QRService::make($qrPayload, $qrPath);
            $info = @getimagesize($qrPath);
            if ($info === false || ($info['mime'] ?? '') !== 'image/png') {
                throw new \RuntimeException('Geçersiz QR PNG: ' . $qrPath);
            }

            // DocxService
            $docxService = new DocxService($tplDir, $this->cfg['paths']['output']);
            $docxService->setLogger(function (string $msg) {
                file_put_contents(__DIR__ . '/../../docx_debug.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
            });

            // Render (without header/footer initially)
            $renderedDocs = [];
            $renderedDocs[] = $docxService->renderTemplate(
                $tplDir . DIRECTORY_SEPARATOR . $mainTpl,
                $vars
            );
            foreach ($ekTpls as $ek) {
                $renderedDocs[] = $docxService->renderTemplate(
                    $tplDir . DIRECTORY_SEPARATOR . $ek,
                    $vars
                );
            }

            foreach ($renderedDocs as $p) {
                if (!is_file($p)) throw new \RuntimeException('Render başarısız: ' . $p);
                clearstatcache(true, $p);
                if (filesize($p) < 1024) throw new \RuntimeException('Render küçük: ' . $p);
            }

            // Birleştir
            if (count($renderedDocs) > 1) {
                $out = $docxService->mergeDocs($renderedDocs, $finalName);
                $check = is_string($out) ? $out : $finalPath;
                clearstatcache(true, $check);
                if (!is_file($check) || filesize($check) < 2048) {
                    throw new \RuntimeException('Birleştirme küçük/oluşmadı: ' . $check);
                }
                $finalDoc = $check;

                foreach ($renderedDocs as $tmp) {
                    if (realpath($tmp) !== realpath($finalDoc)) {
                        @unlink($tmp);
                    }
                }
            } else {
                $only = $renderedDocs[0];
                if (is_file($finalPath)) {
                    @unlink($finalPath);
                }
                if (!@rename($only, $finalPath)) {
                    if (!@copy($only, $finalPath)) {
                        throw new \RuntimeException('Final dosya oluşturulamadı: ' . $finalPath);
                    }
                    @unlink($only);
                }
                $finalDoc = $finalPath;
                clearstatcache(true, $finalDoc);
                if (filesize($finalDoc) < 1024) {
                    throw new \RuntimeException('Final dosya beklenenden küçük: ' . $finalDoc);
                }
            }

            // Apply header/footer with logos and QR
            $hfSpec = [
                'leftImage' => [
                    'path'     => $leftLogo,
                    'widthPx'  => 120,
                    'heightPx' => 36
                ],
                'subject' => $konu,  // Contract subject for header center
                'rightImage' => [
                    'path'     => $rightLogo,
                    'widthPx'  => 120,
                    'heightPx' => 36
                ],
                'qr' => [
                    'path'     => $qrPath,
                    'widthPx'  => 90,
                    'heightPx' => 90
                ]
            ];

            // Re-apply final rendering with header/footer
            // For now, we use renderTemplate on a simple temporary template approach
            // The final document will have headers/footers applied
            try {
                // Copy to temp, render with headers
                $tempPath = $this->cfg['paths']['output'] . DIRECTORY_SEPARATOR . 'temp_' . uniqid() . '.docx';
                @copy($finalDoc, $tempPath);

                // Create temporary template from merged doc
                $vars['SOZLESME_KONU'] = $konu;
                $finalDocWithHeaders = $docxService->renderTemplate($tempPath, $vars, $hfSpec, basename($finalDoc));

                // Replace original with version that has headers
                @unlink($finalDoc);
                @rename($finalDocWithHeaders, $finalDoc);
                @unlink($tempPath);
            } catch (\Exception $e) {
                error_log('[Contract] Header/footer warning: ' . $e->getMessage());
            }

            // Commit the transaction
            $pdo->commit();

            header('Location: /contracts/create?ok=1');
            exit;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Hata: " . $e->getMessage();
        }
    }

    // Generate Word document for existing contract
    public function generateWord(): void
    {
        try {
            $pdo = Database::pdo();
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid contract ID']);
                return;
            }

            // Load contract with all related data
            $contract = Contract::find($pdo, $id);
            if (!$contract) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Contract not found']);
                return;
            }

            // Load related data
            $stmt = $pdo->prepare("SELECT * FROM public.companies WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$contract['contractor_company_id']]);
            $isveren = $stmt->fetch();
            $stmt->execute([$contract['subcontractor_company_id']]);
            $yuklenici = $stmt->fetch();

            $stmt = $pdo->prepare("SELECT * FROM public.project WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$contract['project_id']]);
            $proje = $stmt->fetch();

            if (!$isveren || !$yuklenici || !$proje) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Related data not found']);
                return;
            }

            // Load payment plan
            $stmt = $pdo->prepare("SELECT method, currency, amount, due_date FROM payment_plan WHERE contract_id = ? ORDER BY \"order\"");
            $stmt->execute([$id]);
            $payments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Format payment table for template
            $paymentTableFormatted = \App\Services\MoneyService::formatPaymentTable($payments);

            // Append payment notes if they exist
            if (!empty($contract['payment_notes'])) {
                $paymentTableFormatted .= "\n\nÖdemeler ile İlgili Notlar:\n" . $contract['payment_notes'];
            }

            // Prepare variables - MATCH THE store() METHOD
            $vars = [
                // Contractor (Yüklenici) - Sözleşmenin tarafı
                'SOZLESME_FIRMA_ADI'            => trim($yuklenici['name'] ?? ''),
                'SOZLESME_FIRMA_ADRES'          => trim((($yuklenici['address_line1'] ?? '') . ' ' . ($yuklenici['address_line2'] ?? ''))),
                'SOZLESME_FIRMA_VERGIDAIRESI'   => trim($yuklenici['tax_office'] ?? ''),
                'SOZLESME_FIRMA_VERGINUMARASI'  => trim($yuklenici['tax_number'] ?? ''),
                'SOZLESME_FIRMA_MERSISNO'       => trim($yuklenici['mersis_no'] ?? ''),
                'SOZLESME_FIRMA_TELEFON'        => trim($yuklenici['phone'] ?? ''),
                'SOZLESME_FIRMA_MAIL'           => trim($yuklenici['email'] ?? ''),

                // Employer (İşveren) - Projenin sahibi firma
                'PROJE_ADI'                     => trim($proje['name'] ?? ''),
                'PROJE_FIRMA_ADI'               => trim($isveren['name'] ?? ''),
                'PROJE_FIRMA_ADRES'             => trim((($isveren['address_line1'] ?? '') . ' ' . ($isveren['address_line2'] ?? ''))),
                'PROJE_FIRMA_VERGIDAIRESI'      => trim($isveren['tax_office'] ?? ''),
                'PROJE_FIRMA_VERGINUMARASI'     => trim($isveren['tax_number'] ?? ''),
                'PROJE_FIRMA_MERSISNO'          => trim($isveren['mersis_no'] ?? ''),
                'PROJE_FIRMA_TELEFON'           => trim($isveren['phone'] ?? ''),
                'PROJE_FIRMA_MAIL'              => trim($isveren['email'] ?? ''),
                'PROJE_ADRES'                   => trim($proje['address_line1'] ?? ''),

                // Contract details - Generate title dynamically
                'SOZLESME_TARIH'                => date('d.m.Y', strtotime($contract['contract_date'])),
                'SOZLESME_KONU'                 => $contract['subject'] ?? '',
                'SOZLESME_ADI'                  => ContractNamingService::generateTitle(
                    $proje['short_name'] ?: ($proje['name'] ?? 'PRJ'),
                    $contract['subject'] ?? 'SUBJ',
                    $yuklenici['name'] ?? 'SUBC',
                    $contract['contract_date']
                ),
                'SOZLESME_BEDELI'               => $this->moneyTR((float)$contract['amount'] / 100) . ' ' . ($contract['currency_code'] ?? 'TRY'),
                'SOZLESME_BEDELI_YAZI'          => $contract['amount_in_words'] ?? '',
                'ODEME_PLAN_OZETI'              => $paymentTableFormatted ?: \App\Services\MoneyService::summarizePayments($payments)['text'] ?? '',
                'PROJE_GORSEL'                  => $this->safeImagePath($proje['image_url'] ?? null),
            ];

            // Prepare header/footer spec
            $leftLogo = $this->safeLogoPath($isveren['logo_url'] ?? null, $this->cfg['paths']['assets'] . DIRECTORY_SEPARATOR . 'blank.png');
            $rightLogo = $this->safeLogoPath($yuklenici['logo_url'] ?? null, $this->cfg['paths']['assets'] . DIRECTORY_SEPARATOR . 'blank.png');

            // Generate QR code
            $qrPayload = json_encode([
                'type'        => 'contract',
                'sozlesme_id' => $id,
                'proje_id'    => $contract['project_id'],
            ], JSON_UNESCAPED_UNICODE);
            $qrPath = $this->cfg['paths']['qrcodes'] . DIRECTORY_SEPARATOR . 'qr-' . $id . '.png';
            QRService::make($qrPayload, $qrPath);

            // Select template
            $tplDir = $this->cfg['paths']['templates'];
            $templates = glob($tplDir . DIRECTORY_SEPARATOR . '*.docx') ?: [];
            if (empty($templates)) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'No templates found']);
                return;
            }
            $mainTpl = basename($templates[0]);

            // Render document
            $docxService = new DocxService($tplDir, $this->cfg['paths']['output']);
            $rendered = $docxService->renderTemplate(
                $tplDir . DIRECTORY_SEPARATOR . $mainTpl,
                $vars
            );

            if (!is_file($rendered) || filesize($rendered) < 1024) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Document generation failed']);
                return;
            }

            // Read and serve the file
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="sozlesme_' . $id . '.docx"');
            header('Content-Length: ' . filesize($rendered));
            readfile($rendered);

            @unlink($rendered);
            exit;
        } catch (\Throwable $e) {
            error_log('Generate Word error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Generate PDF for existing contract (placeholder - converts Word to PDF)
    public function generatePdf(): void
    {
        try {
            $pdo = Database::pdo();
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid contract ID']);
                return;
            }

            $contract = Contract::find($pdo, $id);
            if (!$contract) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Contract not found']);
                return;
            }

            // For now, we generate Word and let the client convert it
            // In future, we can use LibreOffice or other tools to convert to PDF
            http_response_code(501);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'PDF generation not yet implemented. Please use the Word file and convert it manually.']);
        } catch (\Throwable $e) {
            error_log('Generate PDF error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Edit form
    public function edit(): void
    {
        $pdo = Database::pdo();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo 'Geçersiz id';
            return;
        }
        $row = Contract::find($pdo, $id);
        if (!$row) {
            http_response_code(404);
            echo 'Sözleşme bulunamadı';
            return;
        }

        // Load payment plan
        $stmtPayments = $pdo->prepare("
            SELECT id, contract_id, method AS type, currency, amount, due_date, \"order\" 
            FROM payment_plan 
            WHERE contract_id = ? 
            ORDER BY \"order\" ASC
        ");
        $stmtPayments->execute([$id]);
        $payments = $stmtPayments->fetchAll(\PDO::FETCH_ASSOC);

        // Convert currency code to currency_id for frontend
        foreach ($payments as &$p) {
            $p['currency_id'] = match ($p['currency'] ?? 'TRY') {
                'EUR' => 978,
                'USD' => 840,
                default => 949
            };
        }

        if (!isset($row['payments'])) {
            $row['payments'] = $payments;
        }

        $this->view('contracts/edit', [
            'title' => 'Sözleşmeyi Düzenle',
            'contract' => $row,
        ]);
    }

    // Update (mevcut basit akış korunuyor)
    public function update(): void
    {
        $pdo = Database::pdo();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(422);
            echo 'Geçersiz sözleşme';
            return;
        }

        $data = $this->collectFormData(isUpdate: true);

        $errors = $this->validate($data, isUpdate: true);
        if ($errors) {
            http_response_code(422);
            echo implode("\n", $errors);
            return;
        }

        try {
            $pdo->beginTransaction();

            Contract::update($pdo, $id, $data);

            // Update payment plan - delete old and insert new
            $stmtDel = $pdo->prepare("DELETE FROM payment_plan WHERE contract_id = ?");
            $stmtDel->execute([$id]);

            // Parse and insert new payment data
            $payloadJson = $_POST['payments_payload'] ?? '[]';
            $paymentRows = json_decode($payloadJson, true) ?: [];

            $stmtIns = $pdo->prepare("
                INSERT INTO payment_plan (contract_id, method, currency, amount, due_date, \"order\")
                VALUES (?,?,?,?,?,?)
            ");

            foreach ($paymentRows as $idx => $row) {
                $method = $row['type'] ?? 'cash';
                $currencyId = $row['currency_id'] ?? 949;
                $amount = (float)($row['amount'] ?? 0);
                if ($amount <= 0) continue;

                $currencyCode = match ($currencyId) {
                    978 => 'EUR',
                    840 => 'USD',
                    default => 'TRY'
                };

                $dueDate = $row['due_date'] ?? null;
                $stmtIns->execute([$id, $method, $currencyCode, $amount, $dueDate, $idx + 1]);
            }

            $pdo->commit();

            // Return JSON response for successful update
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'message' => 'Sözleşme başarıyla güncellendi'], JSON_UNESCAPED_UNICODE);
            return;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            return;
        }
    }

    // Soft delete
    public function destroy(): void
    {
        $pdo = Database::pdo();
        $uuid = $_POST['uuid'] ?? null;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($uuid) {
            Contract::deleteByUuid($pdo, $uuid);
        } elseif ($id > 0) {
            Contract::delete($pdo, $id);
        }
        header('Location: /contracts');
        exit;
    }

    // Bulk upload (değişmedi)
    public function bulkUpload(): void
    {
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        // Set a higher timeout for bulk operations
        set_time_limit(600); // 10 minutes

        try {
            error_log('[Contracts BulkUpload] Starting...');

            $payloadJson = $_POST['payload'] ?? null;
            if (!$payloadJson) {
                $raw = file_get_contents('php://input') ?: '';
                if ($raw) {
                    $parsed = json_decode($raw, true);
                    if (isset($parsed['payload'])) $payloadJson = (string)$parsed['payload'];
                    elseif (isset($parsed['rows']) && is_array($parsed['rows'])) {
                        $payloadJson = json_encode(['rows' => $parsed['rows']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }
            }
            if (!$payloadJson) {
                http_response_code(400);
                echo json_encode(['message' => 'payload missing']);
                ob_end_flush();
                error_log('[Contracts BulkUpload] Error: payload missing');
                return;
            }
            $payload = json_decode($payloadJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['message' => 'payload not valid JSON']);
                ob_end_flush();
                error_log('[Contracts BulkUpload] Error: invalid JSON - ' . json_last_error_msg());
                return;
            }
            $rows = $payload['rows'] ?? null;
            if (!is_array($rows) || count($rows) < 2) {
                http_response_code(422);
                echo json_encode(['message' => 'rows must include header + at least 1 data row']);
                ob_end_flush();
                error_log('[Contracts BulkUpload] Error: rows invalid - count=' . (is_array($rows) ? count($rows) : 'not array'));
                return;
            }

            error_log('[Contracts BulkUpload] Received ' . count($rows) . ' rows (header + ' . (count($rows) - 1) . ' data rows)');
            $headers = array_map(static fn($h) => trim((string)$h), (array)$rows[0]);
            $dataRows = array_slice($rows, 1);
            $allowedColumns = [
                'id',
                'uuid',
                'contractor_company_id',
                'subcontractor_company_id',
                'subcontractor_company_name',
                'contract_date',
                'end_date',
                'subject',
                'project_id',
                'project_name',
                'discipline_id',
                'discipline_name',
                'branch_id',
                'branch_name',
                'amount',
                'currency_code',
                'amount_in_words',
                'is_active',
                'created_at',
                'updated_at',
                'deleted_at'
            ];
            $unknown = array_values(array_diff($headers, $allowedColumns));
            if (!empty($unknown)) {
                http_response_code(422);
                // Create a more specific error message
                $unknownList = array_slice($unknown, 0, 3); // Show first 3 unknown headers
                $moreCount = count($unknown) - 3;
                $message = 'Geçersiz sütun başlığı(ları) Satır 1\'de: "' . implode('", "', $unknownList) . '"';
                if ($moreCount > 0) {
                    $message .= ' ve ' . $moreCount . ' daha fazla';
                }
                echo json_encode(['message' => $message], JSON_UNESCAPED_UNICODE);
                ob_end_flush();
                return;
            }

            // Initialize PDO for name-to-ID conversions
            $pdo = Database::pdo();

            $prepared = [];
            $firstError = null;
            foreach ($dataRows as $rowIndex => $r) {
                if (!is_array($r)) continue;
                $rowNum = $rowIndex + 2; // +2 because headers are row 1 and data starts at row 2
                $rowVals = array_map(static fn($v) => is_null($v) ? '' : (string)$v, $r);
                if (count($rowVals) < count($headers)) $rowVals = array_pad($rowVals, count($headers), '');
                elseif (count($rowVals) > count($headers)) $rowVals = array_slice($rowVals, 0, count($headers));
                $assoc = array_combine($headers, $rowVals);
                if ($assoc === false) continue;
                foreach ($assoc as $k => $v) $assoc[$k] = is_string($v) ? trim($v) : $v;
                if (array_key_exists('uuid', $assoc) && $assoc['uuid'] === '') unset($assoc['uuid']);
                foreach (['is_active'] as $bk) {
                    if (array_key_exists($bk, $assoc)) {
                        $b = $this->strToBoolOrNull($assoc[$bk]);
                        if ($b === null) unset($assoc[$bk]);
                        else $assoc[$bk] = $b;
                    }
                }
                foreach (['created_at', 'updated_at', 'deleted_at'] as $dk) {
                    if (array_key_exists($dk, $assoc)) {
                        $parsed = $this->parseDateOrNull($assoc[$dk]);
                        if ($parsed === null) unset($assoc[$dk]);
                        else $assoc[$dk] = $parsed;
                    }
                }
                foreach (['subject', 'contract_title', 'amount_in_words'] as $tk) {
                    if (array_key_exists($tk, $assoc) && $assoc[$tk] === '') $assoc[$tk] = null;
                }
                // Convert empty strings to null for all text fields to avoid database issues
                foreach ($assoc as $k => $v) {
                    if (is_string($v) && $v === '') {
                        $assoc[$k] = null;
                    }
                }
                foreach (['project_id', 'discipline_id', 'branch_id', 'contractor_company_id', 'subcontractor_company_id'] as $ik) {
                    if (array_key_exists($ik, $assoc)) {
                        $assoc[$ik] = $assoc[$ik] === '' ? null : (is_numeric($assoc[$ik]) ? (int)$assoc[$ik] : null);
                    }
                }
                foreach (['amount'] as $nk) {
                    if (array_key_exists($nk, $assoc)) {
                        $val = str_replace(',', '.', (string)$assoc[$nk]);
                        if ($val === '') {
                            $assoc[$nk] = null;
                        } else $assoc[$nk] = is_numeric($val) ? number_format((float)$val, 2, '.', '') : null;
                    }
                }
                // Parse contract_date with Excel date handling
                if (array_key_exists('contract_date', $assoc)) {
                    if ($assoc['contract_date'] === '' || $assoc['contract_date'] === null) {
                        unset($assoc['contract_date']);
                    } else {
                        $parsed = $this->parseDateOrNull($assoc['contract_date']);
                        if ($parsed === null) {
                            // Date parsing failed - warn user with row and field
                            if ($firstError === null) {
                                $firstError = 'Satır ' . $rowNum . "'de contract_date kolonunda geçersiz tarih formatı: " . $assoc['contract_date'];
                            }
                            continue; // Skip this row, go to next data row
                        }
                        $assoc['contract_date'] = $parsed;
                    }
                }
                // Parse end_date (now stored in contract table as Bitiş Tarihi)
                if (array_key_exists('end_date', $assoc)) {
                    if ($assoc['end_date'] === '' || $assoc['end_date'] === null) {
                        unset($assoc['end_date']);
                    } else {
                        $parsed = $this->parseDateOrNull($assoc['end_date']);
                        if ($parsed === null && $assoc['end_date'] !== '') {
                            // End date parsing failed but it's optional, just skip it
                            unset($assoc['end_date']);
                        } else if ($parsed !== null) {
                            // Successfully parsed, keep it
                            $assoc['end_date'] = $parsed;
                        } else {
                            // Empty value, remove it
                            unset($assoc['end_date']);
                        }
                    }
                }
                if (array_key_exists('id', $assoc)) unset($assoc['id']);

                // Convert company name to ID if provided
                if (!empty($assoc['subcontractor_company_name']) && empty($assoc['subcontractor_company_id'])) {
                    $companyName = $assoc['subcontractor_company_name'];
                    $stmt = $pdo->prepare("SELECT id FROM companies WHERE deleted_at IS NULL AND name ILIKE :name LIMIT 1");
                    $stmt->execute([':name' => $companyName]);
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($result) {
                        $assoc['subcontractor_company_id'] = (int)$result['id'];
                    } else {
                        if ($firstError === null) {
                            $firstError = 'Satır ' . $rowNum . "'de subcontractor_company_name kolonunda şirket \"" . $companyName . "\" bulunamadı";
                        }
                        continue;
                    }
                    unset($assoc['subcontractor_company_name']);
                }

                // Convert project name to ID if provided
                if (!empty($assoc['project_name']) && empty($assoc['project_id'])) {
                    $projectName = $assoc['project_name'];
                    $stmt = $pdo->prepare("SELECT id FROM public.project WHERE deleted_at IS NULL AND name ILIKE :name LIMIT 1");
                    $stmt->execute([':name' => $projectName]);
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($result) {
                        $assoc['project_id'] = (int)$result['id'];
                    } else {
                        if ($firstError === null) {
                            $firstError = 'Satır ' . $rowNum . "'de project_name kolonunda proje \"" . $projectName . "\" bulunamadı";
                        }
                        continue;
                    }
                    unset($assoc['project_name']);
                }

                // Convert discipline name to ID if provided
                if (!empty($assoc['discipline_name']) && empty($assoc['discipline_id'])) {
                    $disciplineName = $assoc['discipline_name'];
                    $stmt = $pdo->prepare("SELECT id FROM public.discipline WHERE name_tr ILIKE :name LIMIT 1");
                    $stmt->execute([':name' => $disciplineName]);
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($result) {
                        $assoc['discipline_id'] = (int)$result['id'];
                    } else {
                        if ($firstError === null) {
                            $firstError = 'Satır ' . $rowNum . "'de discipline_name kolonunda disiplin \"" . $disciplineName . "\" bulunamadı";
                        }
                        continue;
                    }
                    unset($assoc['discipline_name']);
                }

                // Convert branch name to ID if provided
                if (!empty($assoc['branch_name']) && empty($assoc['branch_id'])) {
                    $branchName = $assoc['branch_name'];
                    $disciplineId = $assoc['discipline_id'] ?? null;
                    if (!empty($disciplineId)) {
                        $stmt = $pdo->prepare("SELECT id FROM public.discipline_branch WHERE discipline_id = :discipline_id AND name_tr ILIKE :name LIMIT 1");
                        $stmt->execute([':discipline_id' => $disciplineId, ':name' => $branchName]);
                        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                        if ($result) {
                            $assoc['branch_id'] = (int)$result['id'];
                        } else {
                            if ($firstError === null) {
                                $firstError = 'Satır ' . $rowNum . "'de branch_name kolonunda alt disiplin \"" . $branchName . "\" bulunamadı";
                            }
                            continue;
                        }
                    }
                    unset($assoc['branch_name']);
                }

                // Validate required fields
                $missingFields = [];
                if (empty($assoc['subcontractor_company_id'])) $missingFields[] = 'Yüklenici Firma (subcontractor_company_id)';
                if (empty($assoc['contract_date'])) $missingFields[] = 'Sözleşme Tarihi (contract_date)';
                if (empty($assoc['subject'])) $missingFields[] = 'Konu (subject)';
                if (empty($assoc['project_id']) && empty($assoc['project_name'])) $missingFields[] = 'Proje (project_id veya project_name)';
                // Note: amount is optional, defaults to 0 in database

                if (!empty($missingFields)) {
                    if ($firstError === null) {
                        $firstError = 'Satır ' . $rowNum . ': Zorunlu alanlar eksik: ' . implode(', ', $missingFields);
                    }
                    continue;
                }

                // Apply defaults for columns with NOT NULL constraints
                if (empty($assoc['currency_code'])) {
                    $assoc['currency_code'] = 'TRY';
                }
                if (!isset($assoc['is_active'])) {
                    $assoc['is_active'] = true;
                }

                // Note: contract_title is NOT stored in database
                // It will be generated dynamically using ContractNamingService when needed
                // Remove contract_title from the data to be inserted
                unset($assoc['contract_title']);

                // Use subcontractor_company_id as contractor_company_id if not provided
                if (empty($assoc['contractor_company_id']) && !empty($assoc['subcontractor_company_id'])) {
                    $assoc['contractor_company_id'] = $assoc['subcontractor_company_id'];
                }

                // Validate foreign key references
                if (!empty($assoc['subcontractor_company_id'])) {
                    $stmt = $pdo->prepare("SELECT id FROM public.companies WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                    $stmt->execute([$assoc['subcontractor_company_id']]);
                    if (!$stmt->fetch()) {
                        if ($firstError === null) {
                            $firstError = 'Satır ' . $rowNum . ': Yüklenici Firma ID ' . $assoc['subcontractor_company_id'] . ' bulunamadı';
                        }
                        continue;
                    }
                }

                if (!empty($assoc['contractor_company_id'])) {
                    $stmt = $pdo->prepare("SELECT id FROM public.companies WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                    $stmt->execute([$assoc['contractor_company_id']]);
                    if (!$stmt->fetch()) {
                        if ($firstError === null) {
                            $firstError = 'Satır ' . $rowNum . ': Müteahhit Firma ID ' . $assoc['contractor_company_id'] . ' bulunamadı';
                        }
                        continue;
                    }
                }

                if (!empty($assoc['project_id'])) {
                    $stmt = $pdo->prepare("SELECT id FROM public.project WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                    $stmt->execute([$assoc['project_id']]);
                    if (!$stmt->fetch()) {
                        if ($firstError === null) {
                            $firstError = 'Satır ' . $rowNum . ': Proje ID ' . $assoc['project_id'] . ' bulunamadı';
                        }
                        continue;
                    }
                }

                if (!empty($assoc['discipline_id'])) {
                    $stmt = $pdo->prepare("SELECT id FROM public.discipline WHERE id = ? LIMIT 1");
                    $stmt->execute([$assoc['discipline_id']]);
                    if (!$stmt->fetch()) {
                        if ($firstError === null) {
                            $firstError = 'Satır ' . $rowNum . ': Disiplin ID ' . $assoc['discipline_id'] . ' bulunamadı';
                        }
                        continue;
                    }
                }

                if (!empty($assoc['branch_id'])) {
                    $stmt = $pdo->prepare("SELECT id FROM public.discipline_branch WHERE id = ? LIMIT 1");
                    $stmt->execute([$assoc['branch_id']]);
                    if (!$stmt->fetch()) {
                        if ($firstError === null) {
                            $firstError = 'Satır ' . $rowNum . ': Alt Disiplin ID ' . $assoc['branch_id'] . ' bulunamadı';
                        }
                        continue;
                    }
                }

                // Clean up empty discipline_id and branch_id to prevent foreign key violations
                if (empty($assoc['discipline_id']) || $assoc['discipline_id'] === '0') {
                    unset($assoc['discipline_id']);
                }
                if (empty($assoc['branch_id']) || $assoc['branch_id'] === '0') {
                    unset($assoc['branch_id']);
                }

                // Remove read-only/auto-generated columns
                unset($assoc['id'], $assoc['uuid'], $assoc['created_at'], $assoc['updated_at'], $assoc['deleted_at']);

                // Store the original row number for error reporting
                $assoc['_rowNum'] = $rowNum;
                $prepared[] = $assoc;
            }

            if (empty($prepared)) {
                http_response_code(422);
                if ($firstError !== null) {
                    echo json_encode(['message' => $firstError], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['message' => 'Geçerli veri satırı bulunamadı'], JSON_UNESCAPED_UNICODE);
                }
                ob_end_flush();
                return;
            }

            // Check if there were ANY validation errors collected during loop
            // If so, reject the entire upload without inserting anything
            if ($firstError !== null) {
                http_response_code(400);
                echo json_encode(['message' => $firstError], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                ob_end_flush();
                return;
            }

            $pdo->beginTransaction();
            $groups = [];
            foreach ($prepared as $row) {
                $cols = array_keys($row);
                sort($cols);
                $key = implode('|', $cols);
                if (!isset($groups[$key])) {
                    $groups[$key]['cols'] = $cols;
                    $groups[$key]['rows'] = [];
                }
                $groups[$key]['rows'][] = $row;
            }

            $inserted = 0;
            foreach ($groups as $group) {
                $cols = $group['cols'] ?? [];
                if (empty($cols)) {
                    continue;
                }
                // Exclude _rowNum from the database insert
                $dbCols = array_values(array_filter($cols, static fn($c) => $c !== '_rowNum'));
                if (empty($dbCols)) {
                    continue;
                }
                foreach (['subcontractor_company_id', 'contract_date'] as $req) {
                    if (!in_array($req, $dbCols, true)) {
                        continue 2;
                    }
                }
                $colsSql = '"' . implode('","', $dbCols) . '"';
                $ph = '(' . implode(',', array_fill(0, count($dbCols), '?')) . ')';
                $sql = "INSERT INTO public.contract ($colsSql) VALUES $ph";
                $stmt = $pdo->prepare($sql);
                foreach ($group['rows'] as $row) {
                    $rowNum = $row['_rowNum'] ?? '?';
                    $vals = [];
                    foreach ($dbCols as $c) {
                        $v = $row[$c] ?? null;
                        if (is_bool($v)) $v = $v ? 1 : 0;
                        $vals[] = $v;
                    }
                    try {
                        $stmt->execute($vals);
                        $inserted += $stmt->rowCount();
                    } catch (PDOException $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        http_response_code(500);

                        // Build a more detailed error message with column name
                        $errorMsg = $e->getMessage();
                        $columnName = 'bilinmiyor';

                        // Extract column name from error message
                        if (preg_match('/column "([^"]+)"/', $errorMsg, $matches)) {
                            $columnName = $matches[1];
                        } elseif (preg_match('/foreign key constraint "([^"]+)"/', $errorMsg, $matches)) {
                            $constraint = $matches[1];
                            // Try to extract column from constraint name
                            if (preg_match('/([a-z_]+)_fk/', $constraint, $m)) {
                                $columnName = $m[1];
                            }
                        }

                        $detailMsg = "Satır $rowNum'de " . $columnName . " kolonunda veritabanı hatası: ";

                        // Parse common PostgreSQL errors
                        if (strpos($errorMsg, 'NOT NULL') !== false) {
                            $detailMsg .= "Alan boş bırakılamaz";
                        } elseif (strpos($errorMsg, 'foreign key') !== false || strpos($errorMsg, 'violates foreign key') !== false) {
                            $detailMsg .= "İlgili kayıt bulunamadı";
                        } elseif (strpos($errorMsg, 'UNIQUE') !== false || strpos($errorMsg, 'unique') !== false) {
                            $detailMsg .= "Bu değer zaten kayıtlı - Yinelenen veri";
                        } else {
                            $detailMsg .= $errorMsg;
                        }

                        // Show which columns have values
                        $rowValues = [];
                        foreach ($dbCols as $i => $c) {
                            $v = $vals[$i] ?? null;
                            $rowValues[] = "$c: " . ($v === null ? 'NULL' : (is_string($v) ? "\"$v\"" : $v));
                        }

                        echo json_encode([
                            'message' => $detailMsg,
                            'row' => (int)$rowNum,
                            'column' => $columnName
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        ob_end_flush();
                        return;
                    }
                }
            }

            $pdo->commit();

            http_response_code(200);
            error_log("[Contracts BulkUpload] Success: inserted $inserted records");
            $output = json_encode(['message' => 'ok', 'inserted' => $inserted], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($output === false) {
                http_response_code(500);
                echo json_encode(['message' => 'JSON encoding error', 'inserted' => $inserted]);
            } else {
                echo $output;
            }
            ob_end_flush();
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['message' => 'db error', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ob_end_flush();
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            $msg = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            error_log("[Contracts BulkUpload] Exception: $msg at $file:$line");
            echo json_encode([
                'message' => "Server error: $msg",
                'file' => basename($file),
                'line' => $line
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ob_end_flush();
        }
    }

    // -------- Yardımcılar --------
    private function collectFormData(bool $isUpdate): array
    {
        $g = static function (string $key, ?int $maxLen = null) {
            $val = $_POST[$key] ?? null;
            if ($val === null) return null;
            $val = is_string($val) ? trim($val) : $val;
            if ($val === '') return null;
            if ($maxLen !== null && is_string($val) && mb_strlen($val) > $maxLen) {
                $val = mb_substr($val, 0, $maxLen);
            }
            return $val;
        };
        $toNumber = static function (?string $v): ?string {
            if ($v === null || $v === '') return null;
            $n = str_replace(',', '.', $v);
            return is_numeric($n) ? number_format((float)$n, 2, '.', '') : null;
        };
        return [
            'contractor_company_id' => $g('contractor_company_id'),
            'subcontractor_company_id' => $g('subcontractor_company_id'),
            'contract_date' => $g('contract_date'),
            'end_date' => $g('end_date'),
            'subject' => $g('subject', 255),
            'project_id' => $g('project_id'),
            'discipline_id' => $g('discipline_id'),
            'branch_id' => $g('branch_id'),
            'contract_title' => $g('contract_title', 255),
            'amount' => $toNumber($g('amount')),
            'currency_code' => $g('currency_name'),
            'amount_in_words' => $g('amount_in_words', 1000),
            'payment_notes' => $g('payment_notes', 5000),
        ];
    }

    private function validate(array $data, bool $isUpdate): array
    {
        $errors = [];
        if (empty($data['contractor_company_id'])) $errors[] = 'İşveren firma zorunludur.';
        if (empty($data['subcontractor_company_id'])) $errors[] = 'Yüklenici firma zorunludur.';
        if (empty($data['contract_date'])) $errors[] = 'Sözleşme tarihi zorunludur.';
        return $errors;
    }

    private function toBool($v): bool
    {
        if (is_bool($v)) return $v;
        $v = strtolower((string)$v);
        return in_array($v, ['1', 'true', 'on', 'yes', 'evet'], true);
    }

    private function strToBoolOrNull($v): ?bool
    {
        if ($v === null) return null;
        $s = strtolower(trim((string)$v));
        if ($s === '') return null;

        $trueSet  = ['1', 'true', 'on', 'yes', 'evet', 'y', 't'];
        $falseSet = ['0', 'false', 'off', 'no', 'hayir', 'hayır', 'n', 'f'];

        if (in_array($s, $trueSet, true)) return true;
        if (in_array($s, $falseSet, true)) return false;
        return null;
    }

    private function parseDateOrNull($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;

        // Check if it's an Excel date (numeric format, usually 5 digits)
        if (is_numeric($s)) {
            $num = (float)$s;
            // Excel dates are days since January 1, 1900 (must be positive)
            if ($num > 0 && $num < 60000) { // Reasonable range for Excel dates
                // Convert Excel date to Unix timestamp
                $excelEpoch = new \DateTime('1900-01-01');
                $date = $excelEpoch->modify('+' . intval($num) . ' days');
                return $date->format('Y-m-d H:i:s');
            } else if ($num <= 0 || $num >= 60000) {
                // Invalid numeric value
                return null;
            }
        }

        // Try standard date parsing
        $ts = strtotime($s);
        if ($ts === false) return null;
        return date('Y-m-d H:i:s', $ts);
    }

    // Basit para gösterimi (1.234,56 formatı)
    private function moneyTR(float $val): string
    {
        return number_format($val, 2, ',', '.');
    }

    private function slugFileName(string $name): string
    {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $slug = preg_replace('/[^A-Za-z0-9_\-\.]+/', '-', (string)$slug);
        $slug = trim((string)$slug, '-');
        return strtolower($slug);
    }

    private function safeLogoPath(?string $path, string $fallback): string
    {
        if (!$path) return $fallback;

        // If path already looks like absolute path, check it directly
        if (is_file($path)) return $path;

        // If path starts with /, convert to absolute file path from project root
        if (str_starts_with($path, '/')) {
            $absPath = dirname(__DIR__, 2) . $path;
            if (is_file($absPath)) return $absPath;
        }

        return $fallback;
    }

    private function safeImagePath(?string $path): ?string
    {
        if (!$path) return null;

        // If path already looks like absolute path, check it directly
        if (is_file($path)) return $path;

        // If path starts with /, convert to absolute file path from project root
        if (str_starts_with($path, '/')) {
            $absPath = dirname(__DIR__, 2) . $path;
            if (is_file($absPath)) return $absPath;
        }

        return null;
    }

    // ---- Proje/Şirket API'leri (değişmedi + eklenen: companyTop) ----
    public function projectInfo(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['message' => 'invalid id']);
                return;
            }
            $pdo = \App\Core\Database::pdo();
            $sql = "SELECT p.id, p.name, p.short_name, p.company_id AS employer_company_id, c.name AS employer_company_name
                    FROM public.project p
                    LEFT JOIN public.companies c ON c.id = p.company_id
                    WHERE p.id = :id";
            $st = $pdo->prepare($sql);
            $st->execute([':id' => $id]);
            $row = $st->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['message' => 'not found']);
                return;
            }
            echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['message' => 'server error', 'detail' => $e->getMessage()]);
        }
    }

    public function companySearch(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $q = trim((string)($_GET['q'] ?? ''));
            if ($q === '') {
                echo json_encode([]);
                return;
            }
            $pdo = \App\Core\Database::pdo();
            $st = $pdo->prepare("SELECT id, name FROM companies WHERE deleted_at IS NULL AND name ILIKE :q ORDER BY name LIMIT 100");
            $st->execute([':q' => '%' . $q . '%']);
            echo json_encode($st->fetchAll(\PDO::FETCH_ASSOC) ?: []);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['message' => 'server error', 'detail' => $e->getMessage()]);
        }
    }

    public function companyTop(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = \App\Core\Database::pdo();
            $sql = "
                SELECT c.id, c.name, cnt.usage_count
                FROM companies c
                JOIN (
                  SELECT contractor_company_id AS company_id, COUNT(*) AS usage_count
                  FROM contract
                  WHERE deleted_at IS NULL
                  GROUP BY contractor_company_id
                ) cnt ON cnt.company_id = c.id
                WHERE c.deleted_at IS NULL
                ORDER BY cnt.usage_count DESC, c.name ASC
                LIMIT 20
            ";
            $st = $pdo->query($sql);
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['message' => 'server error', 'detail' => $e->getMessage()]);
        }
    }

    public function companyCreate(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $name = trim((string)($_POST['name'] ?? ''));
            if ($name === '') {
                http_response_code(422);
                echo json_encode(['message' => 'name required']);
                return;
            }
            $pdo = \App\Core\Database::pdo();
            $st = $pdo->prepare("INSERT INTO companies(name, created_at, updated_at) VALUES (:n, now(), now()) RETURNING id, name");
            $st->execute([':n' => $name]);
            $row = $st->fetch(\PDO::FETCH_ASSOC);
            echo json_encode($row ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['message' => 'server error', 'detail' => $e->getMessage()]);
        }
    }

    public function projectList(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = \App\Core\Database::pdo();
            $sql = "SELECT id, name, short_name
                    FROM public.project
                    WHERE deleted_at IS NULL
                    ORDER BY name ASC
                    LIMIT 1000";
            $st = $pdo->query($sql);
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['message' => 'server error', 'detail' => $e->getMessage()]);
        }
    }

    public function companyList(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = \App\Core\Database::pdo();
            $sql = "SELECT id, name
                    FROM public.companies
                    WHERE deleted_at IS NULL
                    ORDER BY name ASC
                    LIMIT 1000";
            $st = $pdo->query($sql);
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['message' => 'server error', 'detail' => $e->getMessage()]);
        }
    }

    // Upload signed PDF for contract
    public function uploadSignedPdf(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = Database::pdo();
            $contractId = (int)($_POST['contract_id'] ?? 0);

            if ($contractId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid contract ID']);
                return;
            }

            // Check if contract exists
            $stmt = $pdo->prepare("SELECT id FROM contract WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$contractId]);
            if (!$stmt->fetchColumn()) {
                http_response_code(404);
                echo json_encode(['error' => 'Contract not found']);
                return;
            }

            // Check if file was uploaded
            if (!isset($_FILES['signed_pdf']) || $_FILES['signed_pdf']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['error' => 'No PDF file uploaded or upload error']);
                return;
            }

            $file = $_FILES['signed_pdf'];
            $mimeType = mime_content_type($file['tmp_name']);

            // Validate file is PDF
            if ($mimeType !== 'application/pdf' && $file['type'] !== 'application/pdf') {
                http_response_code(422);
                echo json_encode(['error' => 'File must be a PDF']);
                return;
            }

            // Create storage directory if needed
            $storagePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'contracts';
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            // Generate filename
            $filename = 'contract_' . $contractId . '_signed_' . time() . '.pdf';
            $filepath = $storagePath . DIRECTORY_SEPARATOR . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save PDF file']);
                return;
            }

            // Update contract with signed PDF path and status
            $stmt = $pdo->prepare("
                UPDATE contract 
                SET signed_pdf_path = ?, status = 'SIGNED', signed_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$filename, $contractId]);

            echo json_encode([
                'ok' => true,
                'message' => 'PDF uploaded successfully',
                'filename' => $filename,
                'status' => 'SIGNED'
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    // Upload output PDF to contract folder named by dynamic title
    public function uploadPdf(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = Database::pdo();
            $contractId = (int)($_POST['id'] ?? 0);

            if ($contractId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid contract ID']);
                return;
            }

            // Get contract with all required data
            $contract = Contract::find($pdo, $contractId);
            if (!$contract) {
                http_response_code(404);
                echo json_encode(['error' => 'Contract not found']);
                return;
            }

            // Get related data to generate dynamic title
            $stmt = $pdo->prepare("SELECT short_name FROM public.project WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$contract['project_id']]);
            $projectShortName = $stmt->fetchColumn() ?: 'PRJ';

            $stmt = $pdo->prepare("SELECT name FROM public.companies WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$contract['subcontractor_company_id']]);
            $companyName = $stmt->fetchColumn() ?: 'CONT';

            // Generate dynamic title
            $contractTitle = ContractNamingService::generateTitle(
                $projectShortName,
                $contract['subject'] ?? 'SUBJ',
                $companyName,
                $contract['contract_date']
            );

            // Check if file was uploaded
            if (!isset($_FILES['pdf'])) {
                http_response_code(400);
                echo json_encode(['error' => 'No file field in request']);
                return;
            }

            if ($_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds php.ini upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
                ];
                $errorMsg = $errorMessages[$_FILES['pdf']['error']] ?? 'Unknown upload error (' . $_FILES['pdf']['error'] . ')';
                http_response_code(400);
                echo json_encode(['error' => $errorMsg]);
                return;
            }

            $file = $_FILES['pdf'];

            // Validate file extension first
            $filename = $file['name'] ?? '';
            $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($fileExt !== 'pdf') {
                http_response_code(422);
                echo json_encode(['error' => 'File must be a PDF (extension: ' . $fileExt . ')']);
                return;
            }

            // Check MIME type using finfo if available, otherwise use file['type']
            $mimeType = $file['type'] ?? '';
            if (function_exists('finfo_file')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
            } elseif (function_exists('mime_content_type')) {
                $mimeType = mime_content_type($file['tmp_name']);
            }

            // Validate file is PDF (accept common PDF MIME types)
            $validPdfMimes = ['application/pdf', 'application/x-pdf', 'application/x-bzpdf', 'application/x-gzpdf'];
            if (!in_array($mimeType, $validPdfMimes, true)) {
                // If mime type check fails, allow it to proceed (browser might have different MIME type)
                // but we've already validated the extension above
            }

            // Create contract documents folder using dynamic title
            $contractFolderPath = $this->cfg['paths']['output'] . DIRECTORY_SEPARATOR . 'contracts' . DIRECTORY_SEPARATOR . $contractTitle;

            // Ensure output directory exists
            if (!is_dir($this->cfg['paths']['output'])) {
                if (!@mkdir($this->cfg['paths']['output'], 0755, true)) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Cannot create output directory']);
                    return;
                }
            }

            // Create contracts subdirectory if needed
            $contractsPath = $this->cfg['paths']['output'] . DIRECTORY_SEPARATOR . 'contracts';
            if (!is_dir($contractsPath)) {
                if (!@mkdir($contractsPath, 0755, true)) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Cannot create contracts directory']);
                    return;
                }
            }

            // Create contract-specific folder using dynamic title
            if (!is_dir($contractFolderPath)) {
                if (!@mkdir($contractFolderPath, 0755, true)) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Cannot create contract folder: ' . basename($contractFolderPath)]);
                    return;
                }
            }

            // Generate filename with contract title
            $newFilename = $contractTitle . '.pdf';
            $filepath = $contractFolderPath . DIRECTORY_SEPARATOR . $newFilename;

            // Move uploaded file
            if (!@move_uploaded_file($file['tmp_name'], $filepath)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save PDF file to: ' . basename($contractFolderPath)]);
                return;
            }

            // Verify file was actually saved
            if (!is_file($filepath)) {
                http_response_code(500);
                echo json_encode(['error' => 'PDF file was not saved correctly']);
                return;
            }

            echo json_encode([
                'ok' => true,
                'message' => 'PDF uploaded successfully',
                'filename' => $filename,
                'folder' => $contractTitle
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    // Serve uploaded PDF file
    public function getUploadedPdf(): void
    {
        try {
            $contractId = (int)($_GET['id'] ?? 0);

            if ($contractId <= 0) {
                http_response_code(400);
                echo 'Invalid contract ID';
                return;
            }

            $pdo = Database::pdo();
            $stmt = $pdo->prepare("SELECT output_pdf_path FROM contract WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$contractId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row || !$row['output_pdf_path']) {
                http_response_code(404);
                echo 'No PDF uploaded for this contract';
                return;
            }

            $filepath = $this->cfg['paths']['output'] . DIRECTORY_SEPARATOR . $row['output_pdf_path'];

            if (!file_exists($filepath)) {
                http_response_code(404);
                echo 'PDF file not found on disk';
                return;
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'Error: ' . $e->getMessage();
        }
    }

    // Get template data for Excel download (projects, disciplines, branches)
    public function templateData(): void
    {
        header('Content-Type: application/json');

        try {
            $pdo = Database::pdo();

            // Get active projects
            $projects = [];
            $stmt = $pdo->prepare("
                SELECT id, name, short_name
                FROM public.project
                WHERE deleted_at IS NULL
                ORDER BY name ASC
            ");
            $stmt->execute();
            $projects = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // Get active companies (for contractor and subcontractor)
            $companies = [];
            $stmt = $pdo->prepare("
                SELECT id, name
                FROM public.companies
                WHERE deleted_at IS NULL
                ORDER BY name ASC
            ");
            $stmt->execute();
            $companies = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // Get disciplines with their branches
            $disciplines = [];
            $stmt = $pdo->prepare("
                SELECT 
                    d.id as discipline_id,
                    d.name_tr as discipline_name,
                    b.id as branch_id,
                    b.name_tr as branch_name
                FROM public.discipline d
                LEFT JOIN public.discipline_branch b ON b.discipline_id = d.id
                ORDER BY d.name_tr ASC, COALESCE(b.name_tr, '') ASC
            ");
            $stmt->execute();
            $disciplines = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            echo json_encode([
                'ok' => true,
                'projects' => $projects,
                'companies' => $companies,
                'disciplines' => $disciplines
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    // Export contracts to Excel with two sheets (contracts + payments)
    public function exportToExcel(): void
    {
        try {
            $pdo = Database::pdo();

            // Fetch all contracts with company names
            $contractsSql = "SELECT 
                c.id, c.uuid, c.contractor_company_id, c1.name AS contractor_name,
                c.subcontractor_company_id, c2.name AS subcontractor_name,
                c.contract_date, c.end_date, c.subject, c.project_id, p.name AS project_name,
                c.discipline_id, c.branch_id, c.contract_title,
                c.amount, c.currency_code, c.amount_in_words, c.is_active,
                c.created_at, c.updated_at
            FROM contract c
            LEFT JOIN companies c1 ON c.contractor_company_id = c1.id
            LEFT JOIN companies c2 ON c.subcontractor_company_id = c2.id
            LEFT JOIN project p ON c.project_id = p.id
            WHERE c.deleted_at IS NULL
            ORDER BY c.created_at DESC";

            $contractsData = $pdo->query($contractsSql)->fetchAll(\PDO::FETCH_ASSOC);

            // Fetch all payments from payment_plan table
            $paymentsSql = "SELECT 
                pp.contract_id, c.contract_title, c.amount, 
                pp.method AS type, pp.due_date, pp.amount AS payment_amount, pp.currency
            FROM payment_plan pp
            JOIN contract c ON pp.contract_id = c.id
            WHERE c.deleted_at IS NULL
            ORDER BY pp.contract_id, pp.due_date";

            $paymentsData = $pdo->query($paymentsSql)->fetchAll(\PDO::FETCH_ASSOC);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'contracts' => $contractsData,
                'payments' => $paymentsData
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
        }
    }

    // ---- Disiplin & Alt Disiplin API'leri (id/text döner) ----
    public function disciplineList(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = Database::pdo();
            $sql = "SELECT
                        id,
                        COALESCE(NULLIF(name_tr, ''), name_en) AS text
                    FROM public.discipline
                    ORDER BY text ASC
                    LIMIT 2000";
            $st = $pdo->query($sql);
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['message' => 'server error', 'detail' => $e->getMessage()]);
        }
    }

    public function disciplineBranchList(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $disciplineId = (int)($_GET['discipline_id'] ?? 0);
            if ($disciplineId <= 0) {
                echo json_encode([]);
                return;
            }
            $pdo = Database::pdo();
            $sql = "SELECT
                        id,
                        COALESCE(NULLIF(name_tr, ''), name_en) AS text
                    FROM public.discipline_branch
                    WHERE discipline_id = :d
                    ORDER BY text ASC NULLS LAST";
            $st = $pdo->prepare($sql);
            $st->execute([':d' => $disciplineId]);
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['message' => 'server error', 'detail' => $e->getMessage()]);
        }
    }

    // Upload document to contract folder named by dynamic title
    public function uploadDocument(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = Database::pdo();
            $contractId = (int)($_POST['contract_id'] ?? 0);

            if ($contractId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid contract ID']);
                return;
            }

            // Get contract with all required data
            $contract = Contract::find($pdo, $contractId);
            if (!$contract) {
                http_response_code(404);
                echo json_encode(['error' => 'Contract not found']);
                return;
            }

            // Get related data to generate dynamic title
            $stmt = $pdo->prepare("SELECT short_name FROM public.project WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$contract['project_id']]);
            $projectShortName = $stmt->fetchColumn() ?: 'PRJ';

            $stmt = $pdo->prepare("SELECT name FROM public.companies WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$contract['subcontractor_company_id']]);
            $companyName = $stmt->fetchColumn() ?: 'CONT';

            // Generate dynamic title
            $contractTitle = ContractNamingService::generateTitle(
                $projectShortName,
                $contract['subject'] ?? 'SUBJ',
                $companyName,
                $contract['contract_date']
            );

            // Check if file was uploaded
            if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['error' => 'No file uploaded or upload error']);
                return;
            }

            $file = $_FILES['document'];

            // Validate file - allow PDFs, Word docs, Excel, Images
            $allowedMimes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/png',
                'image/jpeg',
                'image/jpg',
                'image/gif'
            ];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedMimes) && !in_array($file['type'], $allowedMimes)) {
                http_response_code(422);
                echo json_encode(['error' => 'File type not allowed. Allowed: PDF, DOCX, XLSX, PNG, JPG, GIF']);
                return;
            }

            // Create contract documents folder using dynamic title
            $contractFolderPath = $this->cfg['paths']['output'] . DIRECTORY_SEPARATOR . 'contracts' . DIRECTORY_SEPARATOR . $contractTitle;
            if (!is_dir($contractFolderPath)) {
                mkdir($contractFolderPath, 0755, true);
            }

            // Use original filename
            $filename = basename($file['name']);
            $filePath = $contractFolderPath . DIRECTORY_SEPARATOR . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save file']);
                return;
            }

            echo json_encode([
                'ok' => true,
                'message' => 'Document uploaded successfully',
                'filename' => $filename,
                'folder' => $contractTitle
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    // List documents in contract folder
    public function listDocuments(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $contractId = (int)($_GET['id'] ?? 0);

            if ($contractId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid contract ID']);
                return;
            }

            $contractFolderPath = $this->getContractFolderPathByTitle($contractId);
            if (empty($contractFolderPath)) {
                http_response_code(404);
                echo json_encode(['error' => 'Contract not found']);
                return;
            }

            $documents = [];
            if (is_dir($contractFolderPath)) {
                $files = array_diff(scandir($contractFolderPath), ['.', '..']);
                foreach ($files as $file) {
                    $filePath = $contractFolderPath . DIRECTORY_SEPARATOR . $file;
                    if (is_file($filePath)) {
                        $documents[] = [
                            'name' => $file,
                            'size' => filesize($filePath),
                            'modified' => filemtime($filePath),
                            'url' => '/contracts/download-document?id=' . $contractId . '&file=' . urlencode($file)
                        ];
                    }
                }
            }

            echo json_encode([
                'ok' => true,
                'contract_id' => $contractId,
                'documents' => $documents,
                'folder_path' => $contractFolderPath
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    // Download document from contract folder
    public function downloadDocument(): void
    {
        try {
            $contractId = (int)($_GET['id'] ?? 0);
            $filename = $_GET['file'] ?? '';

            if ($contractId <= 0 || empty($filename)) {
                http_response_code(400);
                echo 'Invalid parameters';
                return;
            }

            // Security: prevent directory traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                http_response_code(403);
                echo 'Access denied';
                return;
            }

            $contractFolderPath = $this->getContractFolderPathByTitle($contractId);
            if (empty($contractFolderPath)) {
                http_response_code(404);
                echo 'Contract not found';
                return;
            }

            $filePath = $contractFolderPath . DIRECTORY_SEPARATOR . $filename;

            // Verify file exists and is in the contract folder
            if (!is_file($filePath) || !str_starts_with(realpath($filePath), realpath($contractFolderPath))) {
                http_response_code(404);
                echo 'File not found';
                return;
            }

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'Error: ' . $e->getMessage();
        }
    }

    // Get contract folder path using dynamic title
    private function getContractFolderPathByTitle(int $contractId): string
    {
        $pdo = Database::pdo();
        $contract = Contract::find($pdo, $contractId);
        if (!$contract) {
            return '';
        }

        // Get related data to generate dynamic title
        $stmt = $pdo->prepare("SELECT name FROM public.project WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$contract['project_id']]);
        $projectName = $stmt->fetchColumn() ?: 'PRJ';

        $stmt = $pdo->prepare("SELECT name FROM public.companies WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$contract['subcontractor_company_id']]);
        $companyName = $stmt->fetchColumn() ?: 'CONT';

        // Generate dynamic title
        $contractTitle = ContractNamingService::generateTitle(
            $projectName,
            $contract['subject'] ?? 'SUBJ',
            $companyName,
            $contract['contract_date']
        );

        return $this->cfg['paths']['output'] . DIRECTORY_SEPARATOR . 'contracts' . DIRECTORY_SEPARATOR . $contractTitle;
    }

    // Get contract folder path
    private function getContractFolderPath(string $contractUuid): string
    {
        return $this->cfg['paths']['output'] . DIRECTORY_SEPARATOR . 'contracts' . DIRECTORY_SEPARATOR . $contractUuid;
    }

    // Open contract folder (for Windows/Mac file explorer)
    public function openDocumentFolder(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $contractId = (int)($_GET['id'] ?? 0);

            if ($contractId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid contract ID']);
                return;
            }

            $contractFolderPath = $this->getContractFolderPathByTitle($contractId);
            if (empty($contractFolderPath)) {
                http_response_code(404);
                echo json_encode(['error' => 'Contract not found']);
                return;
            }

            // Return folder path - frontend will handle opening via JavaScript
            echo json_encode([
                'ok' => true,
                'folder_path' => $contractFolderPath
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    // Delete document from contract folder
    public function deleteDocument(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $contractId = (int)($_POST['id'] ?? 0);
            $filename = $_POST['file'] ?? '';

            if ($contractId <= 0 || empty($filename)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid parameters']);
                return;
            }

            // Security: prevent directory traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid filename']);
                return;
            }

            $contractFolderPath = $this->getContractFolderPathByTitle($contractId);
            if (empty($contractFolderPath)) {
                http_response_code(404);
                echo json_encode(['error' => 'Contract not found']);
                return;
            }

            $filePath = $contractFolderPath . DIRECTORY_SEPARATOR . $filename;

            // Verify file exists and is in the contract folder
            if (!is_file($filePath) || !str_starts_with(realpath($filePath), realpath($contractFolderPath))) {
                http_response_code(404);
                echo json_encode(['error' => 'File not found']);
                return;
            }

            // Delete the file
            if (!@unlink($filePath)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete file']);
                return;
            }

            echo json_encode([
                'ok' => true,
                'message' => 'File deleted successfully',
                'filename' => $filename
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }
}
