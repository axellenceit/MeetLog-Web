<?php

require_once __DIR__ . '/../src/init.php';

if (!$auth->isLoggedIn()) {
    $redirect->to('../login.php');
}

require_once __DIR__ . '/../vendor/autoload.php';

if (!class_exists('TCPDF')) {
    throw new RuntimeException('TCPDF wurde über Composer nicht geladen.');
}

$meetingId = isset($_GET['meetingId']) ? (int)$_GET['meetingId'] : 0;
$numberOfParticipants = isset($_GET['numberOfParticipants']) ? max(1, (int)$_GET['numberOfParticipants']) : 12;

$meeting = null;
if ($meetingId > 0) {
    $meeting = $meetingRepository->findById($meetingId);
}

$organization = null;
if ($meeting && isset($meeting->organizationId)) {
    $organization = $organizationRepository->findById((int)$meeting->organizationId);
}

class MeetingPDF extends TCPDF
{
    private ?object $organization = null;
    private string $meetingTitle = '';
    private string $printDate = '';
    private string $baseDir = '';

    public function setOrganization(?object $organization): void
    {
        $this->organization = $organization;
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

        if ($this->organization) {
            $this->SetFont('helvetica', 'B', 12);
            $this->SetTextColor(64, 64, 64);
            $this->SetXY($textX, $textY);
            $this->Cell($textWidth, 5, (string)($this->organization->name1 ?? ''), 0, 1, 'L', false);

            $this->SetFont('helvetica', '', 10);
            $street = trim((string)($this->organization->street ?? ''));
            $zipCode = trim((string)($this->organization->zipCode ?? ''));
            $city = trim((string)($this->organization->city ?? ''));

            $line2 = trim($street . ' • ' . $zipCode . ' ' . $city, ' •');
            if ($line2 !== '') {
                $this->setX($textX);
                $this->Cell($textWidth, 5, $line2, 0, 1, 'L', false);
            }

            $homepage = trim((string)($this->organization->homepage ?? ''));
            $email = trim((string)($this->organization->email ?? ''));
            if ($homepage !== '' || $email !== '') {
                $line3 = trim($homepage . ' • ' . $email, ' •');
                $this->setX($textX);
                $this->Cell($textWidth, 5, $line3, 0, 1, 'L', false);
            }

            $registryCourt = trim((string)($this->organization->registryCourt ?? ''));
            $registryEntry = trim((string)($this->organization->registryEntry ?? ''));
            if ($registryCourt !== '' || $registryEntry !== '') {
                $registerText = 'Amtsgericht: ' . $registryCourt;
                if ($registryEntry !== '') {
                    $registerText .= ' • ' . $registryEntry;
                }

                $this->setX($textX);
                $this->Cell($textWidth, 5, trim($registerText, ' •'), 0, 1, 'L', false);
            }

            $bottomContentY = $this->GetY();

            if (!empty($this->organization->logo)) {
                $logoFile = $this->baseDir . $this->organization->logo;

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

        $footerY = -20;
        $this->SetY($footerY);

        $lineY = $this->GetY() - 2;
        $this->SetDrawColor(180, 180, 180);
        $this->SetLineWidth(0.2);
        $this->Line($leftMargin, $lineY, $pageWidth - $rightMargin, $lineY);

        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(108, 117, 125);

        $this->SetX($leftMargin);
        $this->Cell(0, 4, $this->meetingTitle . ' - Teilnehmerliste', 0, 1, 'L', false);

        $this->SetX($leftMargin);
        $this->Cell(0, 4, 'Druckdatum: ' . $this->printDate, 0, 0, 'L', false);

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
$pdf->SetTitle('Teilnehmerliste blanko');
$pdf->SetSubject('Teilnehmerliste');
$pdf->setHeaderFont(['helvetica', '', 12]);
$pdf->setFooterFont(['helvetica', '', 8]);
$pdf->SetDefaultMonospacedFont('courier');

$pdf->setOrganization($organization);
$pdf->setMeetingTitle(($meeting ? $meeting->title : 'Teilnehmerliste blanko'));
$pdf->setPrintDate(date('d.m.Y'));
$pdf->setBaseDir(__DIR__);

$pdf->SetPrintHeader(true);
$pdf->SetPrintFooter(true);
$pdf->SetMargins(25, 30, 20);
$pdf->SetHeaderMargin(12);
$pdf->SetFooterMargin(12);
$pdf->SetAutoPageBreak(true, 28);

$filename = 'Teilnehmerliste_blanko_' . date('Y-m-d') . '.pdf';

for ($firstParticipant = 0; $firstParticipant < $numberOfParticipants; $firstParticipant += 12) {
    $pdf->AddPage();

    $pdf->SetTextColor(0, 0, 0);

    // Meeting title
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->MultiCell(0, 8, $meeting ? $meeting->title : 'Teilnehmerliste blanko', 0, 'C');

    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetDrawColor(100, 100, 100);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());

    $pdf->Ln(3);

    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, 'Anlage: Teilnehmerliste', 0, 1, 'L');

    $leftMargin = 25;
    $rightMargin = 20;
    $tableWidth = $pdf->getPageWidth() - $leftMargin - $rightMargin;
    $nameColWidth = $tableWidth * 0.65;
    $statusColWidth = $tableWidth - $nameColWidth;
    $rowHeight = 16;

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetXY($leftMargin, $pdf->GetY());
    $pdf->Cell($nameColWidth, 8, 'Teilnehmer', 1, 0, 'L', true);
    $pdf->Cell($statusColWidth, 8, 'Status', 1, 1, 'C', true);

    $emptyRows = min(12, $numberOfParticipants - $firstParticipant);
    for ($row = 0; $row < $emptyRows; $row++) {
        $pdf->SetXY($leftMargin, $pdf->GetY());
        $pdf->Cell($nameColWidth, $rowHeight, '', 1, 0, 'L');
        $pdf->Cell($statusColWidth, $rowHeight, '', 1, 1, 'C');
    }
}

$pdf->Output($filename, 'I');
