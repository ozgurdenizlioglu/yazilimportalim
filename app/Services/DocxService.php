<?php

declare(strict_types=1);

namespace App\Services;

final class DocxService
{
    private string $templatesDir;
    private string $outputDir;
    private $logger = null;

    public function __construct(string $templatesDir, string $outputDir)
    {
        $this->templatesDir = rtrim($templatesDir, '\\/');
        $this->outputDir = rtrim($outputDir, '\\/');
        if (!is_dir($this->outputDir)) {
            @mkdir($this->outputDir, 0775, true);
        }
    }

    public function setLogger(?callable $logger): void
    {
        $this->logger = $logger;
    }

    private function log(string $msg): void
    {
        if (is_callable($this->logger)) {
            try {
                ($this->logger)($msg);
            } catch (\Throwable $e) {
            }
        }
    }

    /**
     * Render template with placeholder replacement and header/footer
     * 
     * @param string $templatePath Full path to .docx template
     * @param array $vars Placeholder replacements: ${KEY} => value
     * @param array $headerFooterSpec Header/footer config with logos and QR
     * @param string|null $outputName Output filename (auto-generated if null)
     * @return string Path to generated DOCX
     */
    public function renderTemplate(
        string $templatePath,
        array $vars = [],
        array $headerFooterSpec = [],
        ?string $outputName = null
    ): string {
        if (!is_file($templatePath)) {
            throw new \RuntimeException('Template not found: ' . $templatePath);
        }

        $tmpName = $outputName ?: (uniqid('doc_', true) . '.docx');
        if (!str_ends_with(strtolower($tmpName), '.docx')) {
            $tmpName .= '.docx';
        }
        $tmp = $this->outputDir . DIRECTORY_SEPARATOR . $tmpName;
        if (!@copy($templatePath, $tmp)) {
            throw new \RuntimeException('Failed to copy template: ' . $tmp);
        }
        $this->log('[DOCX] renderTemplate -> ' . basename($templatePath) . ' -> ' . basename($tmp));

        // Replace placeholders in document body
        if (!empty($vars)) {
            $this->replaceVariables($tmp, $vars);
        }

        // Apply header/footer with logos and QR
        if (!empty($headerFooterSpec)) {
            $this->applyHeaderFooter($tmp, $headerFooterSpec);
        }

        return $tmp;
    }

    /**
     * Merge multiple DOCX files into one
     */
    public function mergeDocs(array $docPaths, string $finalName): string
    {
        if (empty($docPaths)) {
            throw new \RuntimeException('No documents to merge.');
        }
        $finalPath = $this->outputDir . DIRECTORY_SEPARATOR . $finalName;
        if (!@copy($docPaths[0], $finalPath)) {
            throw new \RuntimeException('Failed to copy base document: ' . $finalPath);
        }
        for ($i = 1; $i < count($docPaths); $i++) {
            $this->appendDocx($finalPath, $docPaths[$i]);
        }
        $this->log('[DOCX] mergeDocs -> ' . basename($finalPath) . ' (from ' . count($docPaths) . ' docs)');
        return $finalPath;
    }

    private function appendDocx(string $targetPath, string $appendPath): void
    {
        $zipT = new \ZipArchive();
        $zipA = new \ZipArchive();
        if ($zipT->open($targetPath) !== true) {
            throw new \RuntimeException('Target file not found: ' . $targetPath);
        }
        if ($zipA->open($appendPath) !== true) {
            $zipT->close();
            throw new \RuntimeException('File to append not found: ' . $appendPath);
        }

        try {
            $docPathT = $this->resolveMainDocumentPath($zipT);
            $docPathA = $this->resolveMainDocumentPath($zipA);

            $docT = $zipT->getFromName($docPathT);
            $docA = $zipA->getFromName($docPathA);
            if ($docT === false || $docA === false) {
                throw new \RuntimeException('Failed to read document.xml');
            }

            $bodyStartT = strpos($docT, '<w:body');
            $bodyEndT   = strrpos($docT, '</w:body>');
            $bodyStartA = strpos($docA, '<w:body');
            $bodyEndA   = strrpos($docA, '</w:body>');
            if ($bodyStartT === false || $bodyEndT === false || $bodyStartA === false || $bodyEndA === false) {
                throw new \RuntimeException('w:body not found');
            }

            $contentT = substr($docT, 0, $bodyEndT);
            $tailT    = substr($docT, $bodyEndT);

            $bodyOpenEndA = strpos($docA, '>', $bodyStartA);
            if ($bodyOpenEndA === false) {
                throw new \RuntimeException('w:body opening not found');
            }
            $innerA = substr($docA, $bodyOpenEndA + 1, $bodyEndA - ($bodyOpenEndA + 1));
            $innerA = preg_replace('#<w:sectPr[^>]*>.*?</w:sectPr>#s', '', $innerA);

            $merged = $contentT . $innerA . $tailT;

            $this->zipPutOrFail($zipT, $docPathT, $merged, 'Failed to write merged ' . $docPathT);
            $this->log('[DOCX] appendDocx OK');
        } finally {
            $zipA->close();
            $zipT->close();
        }
    }

    // =============== PLACEHOLDER REPLACEMENT ===============

    private function replaceVariables(string $docxPath, array $vars): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException('Failed to open DOCX: ' . $docxPath);
        }

        try {
            $docPath = $this->resolveMainDocumentPath($zip);
            $docPathCI = $this->findCaseInsensitive($zip, $docPath) ?? $docPath;

            $documentXml = $zip->getFromName($docPathCI);
            if ($documentXml === false) {
                throw new \RuntimeException('Failed to read ' . $docPathCI);
            }

            $documentXml = $this->replacePlaceholdersRunAware($documentXml, $vars);
            $this->zipPutOrFail($zip, $docPathCI, $documentXml, 'Failed to write ' . $docPathCI);
            $this->log('[DOCX] replaceVariables OK');
        } finally {
            $zip->close();
        }
    }

    private function replacePlaceholdersRunAware(string $xml, array $vars): string
    {
        if (empty($vars)) return $xml;
        if (strpos($xml, '${') === false) return $xml;

        $out = '';
        $offset = 0;
        $patternP = '#<w:p\b[^>]*>.*?</w:p>#s';

        uksort($vars, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        if (!preg_match_all($patternP, $xml, $matches, PREG_OFFSET_CAPTURE)) {
            return $this->simpleInlineReplace($xml, $vars);
        }

        foreach ($matches[0] as $m) {
            [$pXml, $pos] = $m;
            $out .= substr($xml, $offset, $pos - $offset);
            $offset = $pos + strlen($pXml);
            $newP = $this->processParagraphForPlaceholders($pXml, $vars);
            $out .= $newP;
        }

        $out .= substr($xml, $offset);
        return $out;
    }

    private function processParagraphForPlaceholders(string $pXml, array $vars): string
    {
        if (!preg_match('#^<w:p\b[^>]*>(.*)</w:p>$#s', $pXml, $pm)) {
            return $pXml;
        }
        $inner = $pm[1];

        if (!preg_match_all('#<w:r\b[^>]*>.*?</w:r>#s', $inner, $runs, PREG_OFFSET_CAPTURE)) {
            return $pXml;
        }

        $buffer = '';
        $runPieces = [];
        foreach ($runs[0] as $idx => $r) {
            [$rXml, $rPos] = $r;
            $runPieces[$idx] = $rXml;

            if (preg_match_all('#<w:t\b[^>]*>(.*?)</w:t>#s', $rXml, $texts)) {
                foreach ($texts[1] as $tPiece) {
                    $tDecoded = $this->xmlTextDecode($tPiece);
                    $buffer .= $tDecoded;
                }
            }
        }

        if (strpos($buffer, '${') === false) {
            return $pXml;
        }

        $replacements = [];
        foreach ($vars as $k => $v) {
            $needle = '${' . $k . '}';
            $pos = 0;
            while (($hit = strpos($buffer, $needle, $pos)) !== false) {
                $replacements[] = ['start' => $hit, 'end' => $hit + strlen($needle), 'value' => (string)($v ?? '')];
                $pos = $hit + 1;
            }
        }
        if (empty($replacements)) return $pXml;

        usort($replacements, function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });
        $filtered = [];
        $lastEnd = -1;
        foreach ($replacements as $rp) {
            if ($rp['start'] >= $lastEnd) {
                $filtered[] = $rp;
                $lastEnd = $rp['end'];
            }
        }

        $newBuffer = '';
        $cursor = 0;
        foreach ($filtered as $rp) {
            $newBuffer .= substr($buffer, $cursor, $rp['start'] - $cursor);
            $newBuffer .= $rp['value'];
            $cursor = $rp['end'];
        }
        $newBuffer .= substr($buffer, $cursor);

        $cleanText = htmlspecialchars($newBuffer, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $singleRun = '<w:r><w:t xml:space="preserve">' . $cleanText . '</w:t></w:r>';

        $pPr = '';
        if (preg_match('#^(\s*<w:pPr\b[^>]*>.*?</w:pPr>\s*)(.*)$#s', $inner, $m)) {
            $pPr = $m[1];
        }

        return '<w:p>' . $pPr . $singleRun . '</w:p>';
    }

    private function simpleInlineReplace(string $xml, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $xml = str_replace(
                '${' . $k . '}',
                htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $xml
            );
        }
        return $xml;
    }

    private function xmlTextDecode(string $s): string
    {
        return str_replace(['&lt;', '&gt;', '&amp;', '&quot;', '&apos;'], ['<', '>', '&', '"', "'"], $s);
    }

    // =============== HEADER/FOOTER ===============

    /**
     * Apply header/footer to document
     * 
     * Header layout: [contractor logo] | [subject] | [subcontractor logo]
     * Footer layout: [empty] | [QR code] | [page number X/Y]
     * 
     * $spec should contain:
     * - leftImage: ['path' => '...', 'widthPx' => 120, 'heightPx' => 36]
     * - rightImage: ['path' => '...', 'widthPx' => 120, 'heightPx' => 36]
     * - qr: ['path' => '...', 'widthPx' => 90, 'heightPx' => 90]
     * - subject: 'Contract subject text' (for header middle)
     */
    private function applyHeaderFooter(string $docPath, array $spec): void
    {
        $this->log('[DOCX] applyHeaderFooter START');

        $zip = new \ZipArchive();
        if ($zip->open($docPath) !== true) {
            throw new \RuntimeException('Failed to open DOCX: ' . $docPath);
        }

        try {
            $docPartPath = $this->resolveMainDocumentPath($zip);
            $docPartPathCI = $this->findCaseInsensitive($zip, $docPartPath) ?? $docPartPath;

            // Ensure directories exist
            foreach (['word', 'word/_rels', 'word/media'] as $dir) {
                if ($zip->locateName(rtrim($dir, '/') . '/', \ZipArchive::FL_NODIR) === false) {
                    $zip->addEmptyDir(rtrim($dir, '/'));
                }
            }

            // Load and embed images
            $mediaMap = [];
            $relIdx = 1;

            $this->embedImage($zip, $spec['leftImage'] ?? null, 'left', $mediaMap, $relIdx);
            $this->embedImage($zip, $spec['rightImage'] ?? null, 'right', $mediaMap, $relIdx);
            $this->embedImage($zip, $spec['qr'] ?? null, 'qr', $mediaMap, $relIdx);

            // Build and write header
            $headerRelsXml = $this->buildHeaderFooterRels($mediaMap, ['left', 'right']);
            $this->zipPutOrFail($zip, 'word/_rels/header1.xml.rels', $headerRelsXml, 'Failed to write header.rels');

            $subject = trim((string)($spec['subject'] ?? ''));
            $headerXml = $this->buildHeader($mediaMap, $subject);
            $this->zipPutOrFail($zip, 'word/header1.xml', $headerXml, 'Failed to write header.xml');

            // Build and write footer
            $footerRelsXml = $this->buildHeaderFooterRels($mediaMap, ['qr']);
            $this->zipPutOrFail($zip, 'word/_rels/footer1.xml.rels', $footerRelsXml, 'Failed to write footer.rels');

            $footerXml = $this->buildFooter($mediaMap);
            $this->zipPutOrFail($zip, 'word/footer1.xml', $footerXml, 'Failed to write footer.xml');

            // Link header/footer to document
            $this->attachHeaderFooterToDocument($zip, $docPartPathCI);

            // Update [Content_Types].xml
            $this->updateContentTypes($zip);

            $this->log('[DOCX] applyHeaderFooter END OK');
        } finally {
            $zip->close();
        }
    }

    private function embedImage(\ZipArchive $zip, ?array $imgSpec, string $key, array &$mediaMap, int &$relIdx): void
    {
        if (!$imgSpec) return;
        $p = $imgSpec['path'] ?? null;
        if (!$p || !is_file($p)) return;

        $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION) ?: 'png');
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'], true)) $ext = 'png';

        $bin = @file_get_contents($p);
        if ($bin === false) throw new \RuntimeException('Failed to read image: ' . $p);

        $base = $key . '_' . uniqid('', true) . '.' . $ext;
        $this->zipPutOrFail($zip, 'word/media/' . $base, $bin, 'Failed to write media');

        $rid = 'rId' . ($relIdx++);
        $mediaMap[$key] = ['file' => $base, 'rid' => $rid, 'ext' => $ext];
    }

    private function buildHeaderFooterRels(array $mediaMap, array $keys): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($keys as $k) {
            if (!empty($mediaMap[$k])) {
                $rid = htmlspecialchars($mediaMap[$k]['rid'], ENT_QUOTES);
                $file = htmlspecialchars('media/' . $mediaMap[$k]['file'], ENT_QUOTES);
                $xml .= '<Relationship Id="' . $rid . '" '
                    . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" '
                    . 'Target="' . $file . '"/>';
            }
        }
        $xml .= '</Relationships>';
        return $xml;
    }

    /**
     * Build header XML: [left logo] | [subject] | [right logo]
     */
    private function buildHeader(array $mediaMap, string $subject): string
    {
        $leftImg = $mediaMap['left'] ?? null;
        $rightImg = $mediaMap['right'] ?? null;

        $pxLeftW  = 120;
        $pxLeftH  = 36;
        $pxRightW = 120;
        $pxRightH = 36;

        $leftCell = $this->buildImageCell($leftImg, $pxLeftW, $pxLeftH, 'LeftLogo', 15);
        $centerCell = $this->buildTextCell($subject, 'center', 70);
        $rightCell = $this->buildImageCell($rightImg, $pxRightW, $pxRightH, 'RightLogo', 15);

        $tbl = '<w:tbl>'
            . '<w:tblPr><w:tblW w:w="5000" w:type="pct"/></w:tblPr>'
            . '<w:tr>'
            . $leftCell . $centerCell . $rightCell
            . '</w:tr>'
            . '</w:tbl>';

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . $tbl
            . '</w:hdr>';

        return $xml;
    }

    /**
     * Build footer XML: [empty] | [QR code] | [page number X/Y]
     */
    private function buildFooter(array $mediaMap): string
    {
        $qr = $mediaMap['qr'] ?? null;
        $qrW = 90;
        $qrH = 90;

        $leftCell = $this->buildTextCell('', 'left', 33);
        $centerCell = $this->buildImageCell($qr, $qrW, $qrH, 'QRCode', 34);
        $rightCell = $this->buildPageNumberCell(33);

        $tbl = '<w:tbl>'
            . '<w:tblPr><w:tblW w:w="5000" w:type="pct"/></w:tblPr>'
            . '<w:tr>'
            . $leftCell . $centerCell . $rightCell
            . '</w:tr>'
            . '</w:tbl>';

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . $tbl
            . '</w:ftr>';

        return $xml;
    }

    private function buildImageCell(?array $img, int $pxW, int $pxH, string $docPrName, int $widthPct): string
    {
        $w = (int)round($widthPct * 50);
        if (!$img) {
            return '<w:tc><w:tcPr><w:tcW w:w="' . $w . '" w:type="pct"/><w:vAlign w:val="center"/></w:tcPr>'
                . '<w:p></w:p>'
                . '</w:tc>';
        }

        $rid = htmlspecialchars($img['rid'], ENT_QUOTES, 'UTF-8');
        $cx = (int)round($pxW * 9525);
        $cy = (int)round($pxH * 9525);
        $docPrNameEsc = htmlspecialchars($docPrName, ENT_QUOTES, 'UTF-8');

        $drawing = '<w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">'
            . '<wp:extent cx="' . $cx . '" cy="' . $cy . '"/>'
            . '<wp:effectExtent l="0" t="0" r="0" b="0"/>'
            . '<wp:docPr id="1" name="' . $docPrNameEsc . '"/>'
            . '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            . '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:nvPicPr><pic:cNvPr id="0" name=""/><pic:cNvPicPr/></pic:nvPicPr>'
            . '<pic:blipFill><a:blip r:embed="' . $rid . '" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/>'
            . '<a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            . '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
            . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>'
            . '</pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing>';

        return '<w:tc><w:tcPr><w:tcW w:w="' . $w . '" w:type="pct"/><w:vAlign w:val="center"/></w:tcPr>'
            . '<w:p><w:r>' . $drawing . '</w:r></w:p>'
            . '</w:tc>';
    }

    private function buildTextCell(string $text, string $align, int $widthPct): string
    {
        $w = (int)round($widthPct * 50);
        $pPr = '<w:pPr><w:jc w:val="' . $align . '"/></w:pPr>';
        $run = '';
        if (!empty($text)) {
            $t = htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $run = '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="24"/></w:rPr><w:t>' . $t . '</w:t></w:r>';
        }

        return '<w:tc><w:tcPr><w:tcW w:w="' . $w . '" w:type="pct"/><w:vAlign w:val="center"/></w:tcPr>'
            . '<w:p>' . $pPr . $run . '</w:p>'
            . '</w:tc>';
    }

    private function buildPageNumberCell(int $widthPct): string
    {
        $w = (int)round($widthPct * 50);
        $pPr = '<w:pPr><w:jc w:val="right"/></w:pPr>';
        $pageNum = '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="20"/></w:rPr>'
            . '<w:fldChar w:fldCharType="begin"/></w:r>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="20"/></w:rPr>'
            . '<w:instrText xml:space="preserve"> PAGE </w:instrText></w:r>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="20"/></w:rPr>'
            . '<w:fldChar w:fldCharType="separate"/></w:r>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="20"/></w:rPr>'
            . '<w:t>1</w:t></w:r>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="20"/></w:rPr>'
            . '<w:fldChar w:fldCharType="end"/></w:r>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="20"/></w:rPr>'
            . '<w:t>/</w:t></w:r>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="20"/></w:rPr>'
            . '<w:fldChar w:fldCharType="begin"/></w:r>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="20"/></w:rPr>'
            . '<w:instrText xml:space="preserve"> NUMPAGES </w:instrText></w:r>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="20"/></w:rPr>'
            . '<w:fldChar w:fldCharType="separate"/></w:r>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="20"/></w:rPr>'
            . '<w:t>1</w:t></w:r>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="20"/></w:rPr>'
            . '<w:fldChar w:fldCharType="end"/></w:r>';

        return '<w:tc><w:tcPr><w:tcW w:w="' . $w . '" w:type="pct"/><w:vAlign w:val="center"/></w:tcPr>'
            . '<w:p>' . $pPr . $pageNum . '</w:p>'
            . '</w:tc>';
    }

    private function attachHeaderFooterToDocument(\ZipArchive $zip, string $docPartPathCI): void
    {
        // Get document.rels
        $relsPathForDoc = $this->relsPathForPart($docPartPathCI);
        $relsPathForDocCI = $this->findCaseInsensitive($zip, $relsPathForDoc) ?? $relsPathForDoc;

        $docRels = $zip->getFromName($relsPathForDocCI);
        if ($docRels === false) {
            $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
        }

        // Ensure header/footer relationships
        [$docRels, $headerRid] = $this->ensureRelInRelsXml(
            $docRels,
            'http://schemas.openxmlformats.org/officeDocument/2006/relationships/header',
            'header1.xml'
        );
        [$docRels, $footerRid] = $this->ensureRelInRelsXml(
            $docRels,
            'http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer',
            'footer1.xml'
        );
        $this->zipPutOrFail($zip, $relsPathForDocCI, $docRels, 'Failed to write document.rels');

        // Update document.xml
        $docXml = $zip->getFromName($docPartPathCI);
        if ($docXml === false) throw new \RuntimeException('Failed to read document.xml');

        $docXml = $this->ensureRNamespaceOnDocument($docXml);
        $docXml = $this->attachHeaderToDocument($docXml, $headerRid);
        $docXml = $this->attachFooterToDocument($docXml, $footerRid);

        $this->zipPutOrFail($zip, $docPartPathCI, $docXml, 'Failed to write document.xml');
    }

    private function ensureRelInRelsXml(string $relsXml, string $type, string $target): array
    {
        if (strpos($relsXml, 'Relationships') === false) {
            $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
        }
        if (preg_match('#<Relationship[^>]+Type="' . preg_quote($type, '#') . '"[^>]+Target="' . preg_quote($target, '#') . '"[^>]*/>#', $relsXml, $m)) {
            if (preg_match('#Id="([^"]+)"#', $m[0], $m2)) {
                return [$relsXml, $m2[1]];
            }
        }
        $rid = 'rId' . mt_rand(1000, 999999);
        $relsXml = str_replace(
            '</Relationships>',
            '<Relationship Id="' . $rid . '" Type="' . htmlspecialchars($type, ENT_QUOTES) . '" Target="' . htmlspecialchars($target, ENT_QUOTES) . '"/></Relationships>',
            $relsXml
        );
        return [$relsXml, $rid];
    }

    private function ensureRNamespaceOnDocument(string $xml): string
    {
        if (!preg_match('#<w:document[^>]+xmlns:r="http://schemas\.openxmlformats\.org/officeDocument/2006/relationships"#', $xml)) {
            $xml = preg_replace(
                '#<w:document([^>]*)>#',
                '<w:document$1 xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">',
                $xml,
                1
            );
        }
        return $xml;
    }

    private function attachHeaderToDocument(string $documentXml, string $rid): string
    {
        $documentXml = preg_replace('#<w:headerReference[^>]+/?>#', '', $documentXml);

        if (preg_match_all('#<w:sectPr[^>]*>.*?</w:sectPr>#s', $documentXml, $all, PREG_OFFSET_CAPTURE)) {
            for ($i = count($all[0]) - 1; $i >= 0; $i--) {
                $cap = $all[0][$i];
                $matchStr = (string)$cap[0];
                $matchOff = (int)$cap[1];
                $updated = preg_replace(
                    '#</w:sectPr>#',
                    '<w:headerReference w:type="default" r:id="' . htmlspecialchars($rid, ENT_QUOTES) . '"/></w:sectPr>',
                    $matchStr,
                    1
                );
                if ($updated !== null) {
                    $documentXml = substr_replace($documentXml, $updated, $matchOff, strlen($matchStr));
                }
            }
        } else {
            $sect = '<w:sectPr><w:headerReference w:type="default" r:id="' . htmlspecialchars($rid, ENT_QUOTES) . '"/></w:sectPr>';
            $documentXml = str_replace('</w:body>', $sect . '</w:body>', $documentXml);
        }
        return $documentXml;
    }

    private function attachFooterToDocument(string $documentXml, string $rid): string
    {
        $documentXml = preg_replace('#<w:footerReference[^>]+/?>#', '', $documentXml);

        if (preg_match_all('#<w:sectPr[^>]*>.*?</w:sectPr>#s', $documentXml, $all, PREG_OFFSET_CAPTURE)) {
            for ($i = count($all[0]) - 1; $i >= 0; $i--) {
                $cap = $all[0][$i];
                $matchStr = (string)$cap[0];
                $matchOff = (int)$cap[1];
                $updated = preg_replace(
                    '#</w:sectPr>#',
                    '<w:footerReference w:type="default" r:id="' . htmlspecialchars($rid, ENT_QUOTES) . '"/></w:sectPr>',
                    $matchStr,
                    1
                );
                if ($updated !== null) {
                    $documentXml = substr_replace($documentXml, $updated, $matchOff, strlen($matchStr));
                }
            }
        } else {
            $sect = '<w:sectPr><w:footerReference w:type="default" r:id="' . htmlspecialchars($rid, ENT_QUOTES) . '"/></w:sectPr>';
            $documentXml = str_replace('</w:body>', $sect . '</w:body>', $documentXml);
        }
        return $documentXml;
    }

    private function updateContentTypes(\ZipArchive $zip): void
    {
        $ctCI = $this->findCaseInsensitive($zip, '[Content_Types].xml') ?? '[Content_Types].xml';
        $ct = $zip->getFromName($ctCI);
        if ($ct === false) throw new \RuntimeException('[Content_Types].xml not found');

        // Add image type declarations
        foreach (['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'bmp' => 'image/bmp', 'webp' => 'image/webp'] as $ext => $type) {
            if (strpos($ct, 'Extension="' . $ext . '"') === false) {
                $ct = str_replace(
                    '</Types>',
                    '  <Default Extension="' . $ext . '" ContentType="' . $type . '"/></Types>',
                    $ct
                );
            }
        }

        // Add header/footer overrides
        if (strpos($ct, 'PartName="/word/header1.xml"') === false) {
            $ct = str_replace(
                '</Types>',
                '<Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/></Types>',
                $ct
            );
        }
        if (strpos($ct, 'PartName="/word/footer1.xml"') === false) {
            $ct = str_replace(
                '</Types>',
                '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/></Types>',
                $ct
            );
        }

        $this->zipPutOrFail($zip, $ctCI, $ct, 'Failed to write [Content_Types].xml');
    }

    // =============== UTILITY METHODS ===============

    private function relsPathForPart(string $partPath): string
    {
        $dir = '';
        $base = $partPath;
        $pos = strrpos($partPath, '/');
        if ($pos !== false) {
            $dir = substr($partPath, 0, $pos);
            $base = substr($partPath, $pos + 1);
        }
        $relsDir = ($dir === '') ? '_rels' : ($dir . '/_rels');
        return $relsDir . '/' . $base . '.rels';
    }

    private function resolveMainDocumentPath(\ZipArchive $zip): string
    {
        if ($zip->locateName('word/document.xml') !== false) {
            return 'word/document.xml';
        }

        $match = $this->findCaseInsensitive($zip, 'word/document.xml');
        if ($match !== null) {
            return $match;
        }

        $ct = $zip->getFromName('[Content_Types].xml');
        if ($ct !== false) {
            if (preg_match('#<Override[^>]*PartName="([^"]+)"[^>]*ContentType="application/vnd\.openxmlformats-officedocument\.wordprocessingml\.document\.main\+xml"[^>]*/>#', $ct, $m)) {
                $part = ltrim($m[1], '/');
                if ($zip->locateName($part) !== false) return $part;
            }
        }

        $candidates = $this->grepEntries($zip, '#(^|/)document\.xml$#i');
        if (!empty($candidates)) {
            return $candidates[0];
        }

        throw new \RuntimeException('Main document path not found');
    }

    private function findCaseInsensitive(\ZipArchive $zip, string $name): ?string
    {
        $target = str_replace('\\', '/', $name);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $st = $zip->statIndex($i);
            if ($st && isset($st['name'])) {
                if (strcasecmp(str_replace('\\', '/', $st['name']), $target) === 0) {
                    return $st['name'];
                }
            }
        }
        return null;
    }

    private function grepEntries(\ZipArchive $zip, string $regex): array
    {
        $ret = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $st = $zip->statIndex($i);
            if ($st && isset($st['name'])) {
                if (preg_match($regex, $st['name'])) {
                    $ret[] = $st['name'];
                }
            }
        }
        return $ret;
    }

    private function zipPutOrFail(\ZipArchive $zip, string $path, $contents, string $errorMsg): void
    {
        $ci = $this->findCaseInsensitive($zip, $path) ?? $path;
        $idx = $zip->locateName($ci, \ZipArchive::FL_NOCASE);
        if ($idx !== false) {
            $zip->deleteIndex($idx);
        }
        $ok = $zip->addFromString($ci, $contents);
        if (!$ok) {
            throw new \RuntimeException($errorMsg . ' (status=' . $zip->status . ', statusSys=' . $zip->statusSys . ')');
        }
        $this->log('[DOCX] wrote: ' . $ci);
    }
}
