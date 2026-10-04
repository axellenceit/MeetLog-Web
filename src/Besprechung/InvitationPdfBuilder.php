<?php

declare(strict_types=1);

namespace Src\Besprechung;

use RuntimeException;
use Src\Core\App;
use TCPDF;

final class InvitationPdfBuilder
{
    /**
     * Baut die Einladungs-PDF für einen einzelnen Teilnehmer und gibt sie als Binärstring zurück.
     */
    public static function render(App $app, Besprechung $besprechung, Besprechungsteilnehmer $teilnehmer): string
    {
        [$pdf,] = self::build($app, $besprechung, $teilnehmer);

        return (string)$pdf->Output('', 'S');
    }

    /**
     * @return array{0: TCPDF, 1: string} PDF-Instanz und vorgeschlagener Dateiname
     */
    public static function build(App $app, Besprechung $besprechung, Besprechungsteilnehmer $teilnehmer): array
    {
        require_once __DIR__ . '/../../vendor/autoload.php';
        require_once __DIR__ . '/MeetingInvitationPdf.php';

        if (!class_exists('TCPDF')) {
            throw new RuntimeException('TCPDF wurde über Composer nicht geladen.');
        }

        $personRepository = $app->personRepository();
        $countryRepository = $app->countryRepository();
        $organisationRepository = $app->organisationRepository();
        $organisationsMitgliedRepository = $app->organisationsMitgliedRepository();
        $besprechungspunktRepository = $app->besprechungspunktRepository();
        $besprechungsteilnehmerRepository = $app->besprechungsteilnehmerRepository();

        $person = $personRepository->findById($teilnehmer->person);
        $land = $countryRepository->findById($person->land);

        $organisation = $organisationRepository->findById($besprechung->organisationId);
        $vorstand = $organisationsMitgliedRepository->getBoardOfDirectors((int)$besprechung->organisationId);
        $besprechungspunkte = $besprechungspunktRepository->findByBesprechungId((int)$besprechung->id, false);
        $alleTeilnehmer = $besprechungsteilnehmerRepository->findByBesprechungID((int)$besprechung->id);

        $filename = 'Einladung_' . str_replace('.', '', str_replace(' ', '_', $besprechung->titel)) . '_' . date('Y-m-d') . '.pdf';

        $pdf = new \MeetingInvitationPdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('MeetLog');
        $pdf->SetAuthor((string)($organisation->name1 ?? 'MeetLog'));
        $pdf->SetTitle('Einladung: ' . $besprechung->titel);
        $pdf->SetSubject('Einladung zur Besprechung');
        $pdf->SetKeywords($besprechung->titel . ', Einladung, Tagesordnung');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(true);
        $pdf->SetMargins(25, 20, 20);
        $pdf->SetFooterMargin(12);
        $pdf->SetAutoPageBreak(true, 28);
        $pdf->setOrganisation($organisation);
        $pdf->setBoardOfDirectors($vorstand);
        $pdf->AddPage();

        $leftMargin = 25.0;
        $rightMargin = 20.0;
        $usableWidth = $pdf->getPageWidth() - $leftMargin - $rightMargin;

        $organisationName = trim((string)($organisation->name1 ?? 'Organisation'));
        $organisationStreet = trim((string)($organisation->strasse ?? ''));
        $organisationCity = trim((string)($organisation->plz ?? '') . ' ' . (string)($organisation->ort ?? ''));

        // DIN 5008: Absenderzeile oberhalb des Anschriftenfelds
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(90, 90, 90);
        $pdf->SetXY($leftMargin, 27);
        $senderLine = $organisationName;
        if ($organisationStreet !== '' || $organisationCity !== '') {
            $senderLine .= ' - ' . trim($organisationStreet . ' - ' . $organisationCity, ' -');
        }
        $pdf->Cell(85, 4, $senderLine, 0, 1, 'L');

        $empfaengerName = trim((string)($person->vorname ?? '') . ' ' . (string)($person->nachname ?? ''));

        // DIN 5008: Anschriftenfeld (Platzhalter)
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($leftMargin, 33);
        $pdf->MultiCell(
            85,
            5,
            $empfaengerName . "\n" .
                ($person->strasse ?? '') . "\n" .
                ($person->plz ?? '') . ' ' . ($person->ort ?? '') . "\n" .
                ($land->nameDe ?? ''),
            0,
            'L',
            false,
            1
        );

        // Datum rechts oben im Informationsbereich
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY($leftMargin + $usableWidth - 55, 48);
        $pdf->Cell(55, 5, date('d.m.Y'), 0, 1, 'R');

        // Betreff
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY($leftMargin, 85);
        $pdf->MultiCell(
            $usableWidth,
            6,
            'Einladung zur ' . (string)$besprechung->titel,
            0,
            'L',
            false,
            1
        );

        $pdf->Ln(2);

        // Förmliche Anrede
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell($usableWidth, 6, 'Sehr geehrte Damen und Herren,', 0, 'L', false, 1);
        $pdf->Ln(1);
        $pdf->MultiCell(
            $usableWidth,
            6,
            'hiermit laden wir Sie zur folgenden Besprechung ein.',
            0,
            'L',
            false,
            1
        );

        $pdf->Ln(2);

        $meetingDate = !empty($besprechung->datum) ? date('d.m.Y', strtotime((string)$besprechung->datum)) : 'N/A';
        $meetingBegin = !empty($besprechung->beginn) ? date('H:i', strtotime((string)$besprechung->beginn)) : 'N/A';
        $meetingEnd = !empty($besprechung->ende) ? date('H:i', strtotime((string)$besprechung->ende)) : 'N/A';
        $meetingLocation = trim((string)($besprechung->ort ?? ''));

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(35, 6, 'Datum:', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, $meetingDate, 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(35, 6, 'Uhrzeit:', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, $meetingBegin . ' bis ' . $meetingEnd, 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(35, 6, 'Ort:', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, $meetingLocation !== '' ? $meetingLocation : 'N/A', 0, 1, 'L');

        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'Tagesordnungspunkte:', 0, 1, 'L');
        $pdf->Ln(1);

        if (!empty($besprechungspunkte)) {
            $numberWidth = 10;
            $indentWidth = 10;
            $main = 0;
            $child = 0;
            $lastMainPrefix = 0;

            foreach ($besprechungspunkte as $punkt) {
                $titel = self::pdfText($punkt->titel ?? '');
                $beschreibung = self::pdfText($punkt->beschreibung ?? '');

                if (empty($punkt->elternPosition) || $punkt->elternPosition == 0) {
                    $main++;
                    $child = 0;
                    $prefix = $main;
                    $lastMainPrefix = $prefix;
                } else {
                    $child++;
                    $prefix = $lastMainPrefix . '.' . $child;
                }

                $pdf->checkPageBreakPublic(24);

                $startX = $pdf->GetX();
                $startY = $pdf->GetY();

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->SetXY($startX, $startY);
                $pdf->MultiCell($numberWidth, 6, $prefix . '.', 0, 'L', false, 0);

                $pdf->SetXY($startX + $numberWidth, $startY);
                $pdf->MultiCell(0, 6, $titel !== '' ? $titel : 'Ohne Titel', 0, 'L', false, 1);

                if ($beschreibung !== '') {
                    $pdf->SetX($startX + $indentWidth);
                    $pdf->SetFont('helvetica', '', 9);
                    $pdf->SetTextColor(80, 80, 80);
                    $pdf->MultiCell(0, 5, $beschreibung, 0, 'L', false, 1);
                    $pdf->SetTextColor(0, 0, 0);
                }

                $pdf->Ln(1.5);
            }
        } else {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, 'Keine Tagesordnungspunkte erfasst', 0, 1, 'L');
        }

        $pdf->Ln(6);

        $vorsitzende = array_values(array_filter(
            $alleTeilnehmer,
            static fn($t) => $t->istSitzungsleitung === true
        ));

        $pdf->SetFont('helvetica', '', 11);

        if (!empty($vorsitzende)) {
            $vorsitzName = trim(
                (string)($vorsitzende[0]->vorname ?? '') . ' ' . (string)($vorsitzende[0]->nachname ?? '')
            );

            $pdf->MultiCell($usableWidth, 6, 'Mit freundlichen Grüßen', 0, 'L', false, 1);
            $pdf->Ln(8);
            $pdf->MultiCell($usableWidth, 6, $vorsitzName !== '' ? $vorsitzName : 'Vorsitz der Sitzung', 0, 'L', false, 1);
            $pdf->MultiCell($usableWidth, 6, 'Vorsitz der Sitzung', 0, 'L', false, 1);
        } else {
            $pdf->MultiCell($usableWidth, 6, 'Mit freundlichen Grüßen', 0, 'L', false, 1);
            $pdf->MultiCell($usableWidth, 6, 'Die Sitzungsleitung', 0, 'L', false, 1);
        }

        // Anlagenverzeichnis: Anhänge mit DruckenBeiEinladung = 'Y'
        $einladungsAnhaenge = $app->besprechungsanhangRepository()->findPrintableByBesprechungId((int)$besprechung->id, 'Einladung');

        if (!empty($einladungsAnhaenge)) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 7, 'Anlagen', 0, 1, 'L');
            $pdf->Ln(2);

            $nr = 1;
            foreach ($einladungsAnhaenge as $anhang) {
                $pdf->checkPageBreakPublic(16);
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(0, 6, 'Anlage ' . $nr . ': ' . $anhang->originalDateiname . ' (' . $anhang->formatierteGroesse() . ')', 0, 1, 'L');

                if (!empty($anhang->beschreibung)) {
                    $pdf->SetFont('helvetica', '', 9);
                    $pdf->SetTextColor(80, 80, 80);
                    $pdf->MultiCell(0, 5, self::pdfText($anhang->beschreibung), 0, 'L', false, 1);
                    $pdf->SetTextColor(0, 0, 0);
                }

                $pdf->Ln(1.5);
                $nr++;
            }

            // Header/Footer für alle folgenden Anhang-Seiten deaktivieren
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Anhang-Seiten vollständig in die Einladung einfügen
            self::appendPdfAttachments($pdf, $einladungsAnhaenge);
        }

        return [$pdf, $filename];
    }

    /**
     * Hängt die Seiten der übergebenen PDF-Anhänge vollständig an das PDF an.
     *
     * @param Besprechungsanhang[] $anhaenge
     */
    private static function appendPdfAttachments(\MeetingInvitationPdf $pdf, array $anhaenge): void
    {
        foreach ($anhaenge as $anhang) {
            if (empty($anhang->dateipfad)) {
                continue;
            }

            // Von src/Besprechung/ aus ist das Projektroot dirname(__DIR__, 2)
            $filePath = dirname(__DIR__, 2) . '/' . ltrim((string)$anhang->dateipfad, './');

            if (!is_file($filePath)) {
                continue;
            }

            $pageCount = $pdf->setSourceFile($filePath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->watermarkAttachment();
                $pdf->useTemplate($templateId);
            }
        }
    }

    private static function pdfText(?string $html): string
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
            '',
            '• ',
            "\n",
            '',
            '',
            '',
            '',
            ''
        ];

        $text = str_ireplace($search, $replace, $text);
        $text = strip_tags($text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{2,}/', "\n\n", $text);
        $text = preg_replace('/\n +/', "\n", $text);

        return trim($text);
    }
}
