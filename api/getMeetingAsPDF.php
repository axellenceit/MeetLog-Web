<?php

require_once __DIR__ . '/../src/init.php';

if (!$auth->isLoggedIn()) {
    $redirect->to('../login.php');
}

if (!isset($_GET['meetingId'])) {
    $redirect->back();
}

$besprechung = $besprechungRepository->findById((int)($_GET['meetingId']));

if (!$besprechung || !$besprechung->istAktiviert) {
    $flash->set('errorMsg', 'Diese Besprechung existiert nicht oder wurde gelöscht');
    $flash->set('errorColor', 'red');
    $flash->set('errorCode', '403');
    $redirect->to('../error.php');
}

require_once __DIR__ . '/../vendor/autoload.php';

if (!class_exists('TCPDF')) {
    throw new RuntimeException('TCPDF wurde über Composer nicht geladen.');
}

// Hole Organisationsdaten
$organisation = $organisationRepository->findById($besprechung->organisationId);
$besprechungspunkte = $besprechungspunktRepository->findByBesprechungId((int)$besprechung->id, false);
$teilnehmer = $besprechungsteilnehmerRepository->findByBesprechungID((int)$besprechung->id);

$filename = 'Besprechungsprotokoll_' . str_replace('.', '', str_replace(' ', '_', $besprechung->titel)) . '_' . date('Y-m-d') . '.pdf';

class MeetingPDF extends TCPDF
{
    private ?object $organisation = null;
    private string $meetingTitle = '';
    private string $printDate = '';
    private string $baseDir = '';

    public function setOrganisation(?object $organisation): void
    {
        $this->organisation = $organisation;
    }

    public function setMeetingTitle(string $meetingTitle): void
    {
        $this->meetingTitle = $meetingTitle;
    }

    public function setPrintDate(string $printDate): void
    {
        $this->printDate = $printDate;
    }

    public function setBaseDir(string $baseDir): void
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    /**
     * Öffentliche Wrapper-Methode für das protected checkPageBreak()
     * Verhindert Seitenumbrüche innerhalb eines Blocks (z. B. Tagesordnungspunkt)
     */
    public function checkPageBreakPublic(float $height = 0): void
    {
        $this->CheckPageBreak($height);
    }

    public function Header(): void
    {
        $leftMargin  = 15;
        $rightMargin = 15;
        $pageWidth   = $this->getPageWidth();
        $usableWidth = $pageWidth - $leftMargin - $rightMargin;

        $headerTopY = 12;

        $logoWidth  = 64;
        $logoHeight = 14;
        $gap        = 6;

        $textWidth = $usableWidth - $logoWidth - $gap;
        $textX = $leftMargin;
        $textY = $headerTopY;

        $bottomContentY = $textY;

        if ($this->organisation) {
            $this->SetFont('helvetica', 'B', 12);
            $this->SetTextColor(64, 64, 64);
            $this->SetXY($textX, $textY);
            $this->Cell($textWidth, 5, (string)($this->organisation->name1 ?? ''), 0, 1, 'L', false);

            $this->SetFont('helvetica', '', 10);

            $strasse = trim((string)($this->organisation->strasse ?? ''));
            $plz = trim((string)($this->organisation->plz ?? ''));
            $ort = trim((string)($this->organisation->ort ?? ''));

            $line2 = trim($strasse . ' • ' . $plz . ' ' . $ort, ' •');
            if ($line2 !== '') {
                $this->setX($textX);
                $this->Cell($textWidth, 5, $line2, 0, 1, 'L', false);
            }

            $homepage = trim((string)($this->organisation->homepage ?? ''));
            $email = trim((string)($this->organisation->email ?? ''));
            if ($homepage !== '' || $email !== '') {
                $line3 = trim($homepage . ' • ' . $email, ' •');
                $this->setX($textX);
                $this->Cell($textWidth, 5, $line3, 0, 1, 'L', false);
            }

            $registergericht = trim((string)($this->organisation->registergericht ?? ''));
            $registereintrag = trim((string)($this->organisation->registereintrag ?? ''));
            if ($registergericht !== '' || $registereintrag !== '') {
                $registerText = 'Amtsgericht: ' . $registergericht;
                if ($registereintrag !== '') {
                    $registerText .= ' • ' . $registereintrag;
                }

                $this->setX($textX);
                $this->Cell($textWidth, 5, trim($registerText, ' •'), 0, 1, 'L', false);
            }

            $bottomContentY = $this->GetY();

            if (!empty($this->organisation->logo)) {
                $logoFile = $this->baseDir . $this->organisation->logo;

                if (is_file($logoFile)) {
                    $logoX = $pageWidth - $rightMargin - $logoWidth;
                    $logoY = $textY;

                    $this->Image(
                        $logoFile,
                        $logoX,
                        $logoY,
                        $logoWidth,
                        $logoHeight,
                        '',
                        '',
                        '',
                        false,
                        300,
                        '',
                        false,
                        false,
                        0,
                        false,
                        false,
                        false
                    );

                    $bottomContentY = max($bottomContentY, $logoY + $logoHeight);
                }
            }
        }

        $bottomLineY = $bottomContentY + 2;

        $this->SetDrawColor(120, 120, 120);
        $this->SetLineWidth(0.2);
        $this->Line($leftMargin, $bottomLineY, $pageWidth - $rightMargin, $bottomLineY);
    }

    public function Footer(): void
    {
        $leftMargin  = 15;
        $rightMargin = 15;
        $pageWidth   = $this->getPageWidth();

        $footerY = -20; // 20 mm vom unteren Rand
        $this->SetY($footerY);

        // Trennlinie oberhalb Footer
        $lineY = $this->GetY() - 2;
        $this->SetDrawColor(180, 180, 180);
        $this->SetLineWidth(0.2);
        $this->Line($leftMargin, $lineY, $pageWidth - $rightMargin, $lineY);

        // Linke Seite: Titel + Druckdatum
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(108, 117, 125); // ähnlich Bootstrap text-muted

        $this->SetX($leftMargin);
        $this->Cell(0, 4, $this->meetingTitle, 0, 1, 'L', false);

        $this->SetX($leftMargin);
        $this->Cell(0, 4, 'Druckdatum: ' . $this->printDate, 0, 0, 'L', false);

        // Rechte Seite: Seitenzahl
        $this->SetY($footerY);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(0, 0, 0);
        $this->SetX($leftMargin);
        $this->Cell(
            $pageWidth - $leftMargin - $rightMargin,
            8,
            'Seite ' . $this->getAliasNumPage() . ' von ' . $this->getAliasNbPages(),
            0,
            0,
            'R',
            false
        );
    }


}

$pdf = new MeetingPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('MeetLog');
$pdf->SetAuthor('MeetLog');
$pdf->SetTitle($besprechung->titel);
$pdf->SetSubject('Besprechungsprotokoll');
$pdf->SetKeywords($besprechung->titel . ', ' . ($besprechung->ort ?? ''));
$pdf->setHeaderFont(['helvetica', '', 12]);
$pdf->setFooterFont(['helvetica', '', 8]);
$pdf->SetDefaultMonospacedFont('courier');

$pdf->setOrganisation($organisation);
$pdf->setMeetingTitle($besprechung->titel);
$pdf->setPrintDate(date('d.m.Y'));
$pdf->setBaseDir(__DIR__);

// Header/Footer aktiv lassen
$pdf->SetPrintHeader(true);
$pdf->SetPrintFooter(true);

// Wichtig: genug Platz für Header und Footer reservieren
$pdf->SetMargins(25, 45, 20);
$pdf->SetHeaderMargin(12);
$pdf->SetFooterMargin(12);
$pdf->SetAutoPageBreak(true, 28);

$pdf->AddPage();

// Titel des Protokolls
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(100, 100, 100);
$pdf->MultiCell(0, 8, $besprechung->titel, 0, 'C');

$pdf->Ln(5);

// Sitzungsinformation
$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(0, 0, 0);

$pdf->Ln(2);

// Sitzungszeit
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(35, 5, 'Sitzungszeit:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, date('d.m.Y', strtotime($besprechung->datum)) . ' (' . date('H:i', strtotime($besprechung->beginn)) . ' -> ' . date('H:i', strtotime($besprechung->ende)) . ')', 0, 1, 'L');

// Sitzungsort
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(35, 5, 'Sitzungsort:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, $besprechung->ort ?? 'N/A', 0, 1, 'L');

// Anwesende
$anwesende = array_filter($teilnehmer, fn($t) => $t->istAnwesend);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(35, 5, 'Anwesende:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 10);
if (!empty($anwesende)) {
    $anwesendeText = implode(', ', array_map(
        fn($t) => trim(($t->vorname ?? '') . ' ' . ($t->nachname ?? '')),
        $anwesende
    ));
    $pdf->MultiCell(0, 5, $anwesendeText, 0, 'L');
} else {
    $pdf->Cell(0, 5, 'Keine Daten erfasst', 0, 1, 'L');
}

// Vorsitz
$vorsitz = array_filter($teilnehmer, fn($t) => $t->istSitzungsleitung === true);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(35, 5, 'Vorsitz:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 10);
if (!empty($vorsitz)) {
    $vorsitzName = current($vorsitz);
    $pdf->Cell(0, 5, trim(($vorsitzName->vorname ?? '') . ' ' . ($vorsitzName->nachname ?? '')), 0, 1, 'L');
} else {
    $pdf->Cell(0, 5, 'N/A', 0, 1, 'L');
}

// Protokollführer
$protokollführer = array_filter($teilnehmer, fn($t) => $t->istProtokollfuehrer === true);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(35, 5, 'Protokollführung:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 10);
if (!empty($protokollführer)) {
    $protoName = current($protokollführer);
    $pdf->Cell(0, 5, trim(($protoName->vorname ?? '') . ' ' . ($protoName->nachname ?? '')), 0, 1, 'L');
} else {
    $pdf->Cell(0, 5, 'N/A', 0, 1, 'L');
}

// Entschuldigte
$entschuldigte = array_filter($teilnehmer, fn($t) => $t->istEntschuldigt);
if (!empty($entschuldigte)) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(35, 5, 'Entschuldigt:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $entschuldigteText = implode(', ', array_map(
        fn($t) => trim(($t->vorname ?? '') . ' ' . ($t->nachname ?? '')),
        $entschuldigte
    ));
    $pdf->MultiCell(0, 5, $entschuldigteText, 0, 'L');
}

// Unterschreibende
$unterschreibende = array_filter($teilnehmer, fn($t) => $t->darfUnterschreiben);
if (!empty($unterschreibende)) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(35, 5, 'Unterschreibend:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $unterschreibendeText = implode(', ', array_map(
        fn($t) => trim(($t->vorname ?? '') . ' ' . ($t->nachname ?? '')),
        $unterschreibende
    ));
    $pdf->MultiCell(0, 5, $unterschreibendeText, 0, 'L');
}

$pdf->Ln(3);

// Trennlinie
$pdf->SetDrawColor(100, 100, 100);
$pdf->SetLineWidth(0.3);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(4);

// Tagesordnungspunkte
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Tagesordnungspunkte:', 0, 1, 'L');
$pdf->Ln(2);

if (!empty($besprechungspunkte)) {
    $numberWidth = 10;
    $indentWidth = 10;

    $main = 0;
    $child = 0;
    $lastMainPrefix = 0;

    foreach ($besprechungspunkte as $punkt) {
        $titel = pdfText($punkt->titel ?? '');
        $beschreibung = pdfText($punkt->beschreibung ?? '');
        $ergebnis = pdfText($punkt->ergebnis ?? '');

        if (empty($punkt->elternPosition) || $punkt->elternPosition == 0):

            $main++;
            $child = 0;
            $prefix = $main;
            $lastMainPrefix = $prefix;

        else:

            $child++;
            $prefix = $lastMainPrefix . '.' . $child;

        endif;

        // Prüfe, ob der gesamte Punkt noch auf die Seite passt
        // Ca. 30 mm Höhe pro Punkt schätzen (kann je nach Inhalt variieren)
        $geschuetzteHoehe = 30;

        // checkPageBreak macht automatisch einen Seitenumbruch, wenn nicht genug Platz
        $pdf->checkPageBreakPublic($geschuetzteHoehe);

        $startX = $pdf->GetX();
        $startY = $pdf->GetY();

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($startX, $startY);
        $pdf->MultiCell($numberWidth, 6, $prefix . '.', 0, 'L', false, 0);

        $pdf->SetXY($startX + $numberWidth, $startY);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->MultiCell(0, 6, $titel !== '' ? $titel : 'Ohne Titel', 0, 'L', false, 1);

        if ($beschreibung !== '') {
            $pdf->SetX($startX + $indentWidth);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(80, 80, 80);
            $pdf->MultiCell(
                0,
                5,
                $punkt->beschreibung,
                0,
                'L',
                false,
                1,
                '',
                '',
                true,
                0,
                true,
                false,
                0,
                'T',
                true
            );
        }

        if ($punkt->typ === 'abstimmung') {
            $ja = (int)($punkt->ergebnis_zustimmung ?? 0);
            $nein = (int)($punkt->ergebnis_ablehnung ?? 0);
            $enthaltung = (int)($punkt->ergebnis_enthaltung ?? 0);

            $x = $startX + $indentWidth;
            $y = $pdf->GetY() + 1.5;

            $pdf->SetXY($x, $y);
            $pdf->SetFont('helvetica', 'B', 9);

            $pdf->SetTextColor(90, 90, 90);
            $pdf->Cell(28, 5, 'Abstimmung:', 0, 0, 'L');

            $pdf->SetTextColor(90, 180, 20);
            $pdf->Cell(10, 5, '+ ' . $ja, 0, 0, 'L');

            $pdf->SetTextColor(220, 40, 40);
            $pdf->Cell(10, 5, '- ' . $nein, 0, 0, 'L');

            $pdf->SetTextColor(120, 120, 120);
            $pdf->Cell(10, 5, '/ ' . $enthaltung, 0, 1, 'L');

            $pdf->SetTextColor(0, 0, 0);
        }

        if ($ergebnis !== '') {
            $pdf->Ln(2);
            $pdf->SetX($startX + $indentWidth);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(0, 100, 0);
            $pdf->MultiCell(0, 5, 'Ergebnis: ' . $ergebnis, 0, 'L', false, 1);
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
    }
} else {
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Keine Tagesordnungspunkte erfasst', 0, 1, 'L');
}

// Unterschriftsbereich
// Keep the whole signature section together on one page.
if (!empty($unterschreibende)) {
    $anzahlUnterschreibende = count($unterschreibende);
    $signatureBlockHeight = 42.0;

    if ($anzahlUnterschreibende > 3) {
        $signatureBlockHeight += 6.0;
    }

    $pdf->checkPageBreakPublic($signatureBlockHeight);
} else {
    $pdf->checkPageBreakPublic(38.0);
}

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 6, 'Gezeichnet am __________________', 0, 1, 'L');

$pdf->Ln(15);
$pdf->SetFont('helvetica', '', 10);

if (!empty($unterschreibende)) {
    $unterschreibendeListe = array_values($unterschreibende);
    $anzahlUnterschreibende = count($unterschreibendeListe);

    $margins = $pdf->getMargins();
    $leftX = (float)$margins['left'];
    $rightX = $pdf->getPageWidth() - (float)$margins['right'];
    $availableWidth = $rightX - $leftX;

    $gap = 8.0;
    $totalGap = $gap * max(0, $anzahlUnterschreibende - 1);
    $columnWidth = ($availableWidth - $totalGap) / $anzahlUnterschreibende;
    $lineWidth = max(20.0, $columnWidth - 6.0);

    $lineY = $pdf->GetY() + 3;
    $nameY = $lineY + 1.8;

    for ($i = 0; $i < $anzahlUnterschreibende; $i++) {
        $x = $leftX + ($i * ($columnWidth + $gap));
        $pdf->Line($x, $lineY, $x + $lineWidth, $lineY);

        $personenName = trim((string)($unterschreibendeListe[$i]->vorname ?? '') . ' ' . (string)($unterschreibendeListe[$i]->nachname ?? ''));
        $pdf->SetXY($x, $nameY);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell($columnWidth, 4, $personenName, 0, 0, 'L');
    }

    $pdf->Ln(10);
} else {
    $pdf->Cell(0, 6, 'Unterschrift: ' . str_repeat('_', 50), 0, 1, 'L');
    $pdf->Ln(7);
    $pdf->Cell(0, 6, 'Name:         ' . str_repeat('_', 30), 0, 1, 'L');
}

$pdf->Output($filename, 'I');

function pdfText(?string $html): string
{
    if (empty($html)) {
        return '';
    }

    $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $search = [
        '<br>',
        '<br/>',
        '<br />',
        '</p>',
        '<li>',
        '</li>',
        '<ol>',
        '</ol>',
        '<ul>',
        '</ul>',
        '<p>'
    ];

    $replace = [
        "\n",
        "\n",
        "\n",
        "",
        "• ",
        "\n",
        '',
        '',
        '',
        '',
        ''
    ];

    $text = str_ireplace($search, $replace, $text);
    $text = strip_tags($text);
    $text = preg_replace("/[ \t]+/", ' ', $text);
    $text = preg_replace("/\n{2,}/", "\n\n", $text);
    $text = preg_replace("/\n +/", "\n", $text);
    $text = trim($text);

    return $text;
}
