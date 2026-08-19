<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

if (!class_exists('TCPDF')) {
    throw new RuntimeException('TCPDF wurde über Composer nicht geladen.');
}

/**
 * TCPDF-Erweiterung für die Einladungs-PDF mit Organisationsfußzeile. Bewusst im globalen
 * Namespace, da TCPDF selbst nicht namespaced ist.
 */
class MeetingInvitationPdf extends TCPDF
{
    private ?object $organisation = null;
    private array $boardOfDirectors = [];

    public function setOrganisation(?object $organisation): void
    {
        $this->organisation = $organisation;
    }

    public function setBoardOfDirectors(array $boardOfDirectors): void
    {
        $this->boardOfDirectors = $boardOfDirectors;
    }

    public function checkPageBreakPublic(float $height = 0): void
    {
        $this->checkPageBreak($height);
    }

    public function Footer(): void
    {
        $leftMargin = 25.0;
        $rightMargin = 20.0;
        $pageWidth = $this->getPageWidth();
        $usableWidth = $pageWidth - $leftMargin - $rightMargin;

        $this->SetY(-25);

        $lineY = $this->GetY() - 1;
        $this->SetDrawColor(170, 170, 170);
        $this->SetLineWidth(0.2);
        $this->Line($leftMargin, $lineY, $pageWidth - $rightMargin, $lineY);

        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(75, 75, 75);

        $nameParts = [];
        $name1 = trim((string)($this->organisation->name1 ?? ''));
        $name2 = trim((string)($this->organisation->name2 ?? ''));
        if ($name1 !== '') {
            $nameParts[] = $name1;
        }
        if ($name2 !== '') {
            $nameParts[] = $name2;
        }

        $street = trim((string)($this->organisation->strasse ?? ''));
        $city = trim((string)($this->organisation->plz ?? '') . ' ' . (string)($this->organisation->ort ?? ''));
        $country = trim((string)($this->organisation->land ?? ''));

        $addressParts = array_filter([$street, $city, $country], static fn($value) => $value !== '');
        $addressLine = implode(' | ', $addressParts);

        $contactParts = [];
        $phone = trim((string)($this->organisation->telefon ?? ''));
        $fax = trim((string)($this->organisation->telefax ?? ''));
        $email = trim((string)($this->organisation->email ?? ''));
        $web = trim((string)($this->organisation->homepage ?? ''));

        if ($phone !== '') {
            $contactParts[] = 'Tel.: ' . $phone;
        }
        if ($fax !== '') {
            $contactParts[] = 'Fax: ' . $fax;
        }
        if ($email !== '') {
            $contactParts[] = 'E-Mail: ' . $email;
        }
        if ($web !== '') {
            $contactParts[] = $web;
        }

        $registergericht = trim((string)($this->organisation->registergericht ?? ''));
        $registereintrag = trim((string)($this->organisation->registereintrag ?? ''));

        $vorstandNamen = [];
        foreach ($this->boardOfDirectors as $mitglied) {
            $vorstandName = trim((string)($mitglied->vorname ?? '') . ' ' . (string)($mitglied->nachname ?? ''));
            if ($vorstandName !== '') {
                $vorstandNamen[] = $vorstandName;
            }
        }

        $registerParts = [];
        if ($registergericht !== '') {
            $registerParts[] = 'Registergericht: ' . $registergericht;
        }
        if ($registereintrag !== '') {
            $registerParts[] = 'Eintrag: ' . $registereintrag;
        }

        if (!empty($vorstandNamen)) {
            $registerParts[] = 'Vorstand: ' . implode(', ', $vorstandNamen);
        }

        $this->SetX($leftMargin);
        $nameAddressParts = [];
        if (!empty($nameParts)) {
            $nameAddressParts[] = implode(' | ', $nameParts);
        }
        if ($addressLine !== '') {
            $nameAddressParts[] = $addressLine;
        }
        $this->Cell($usableWidth, 4, implode(' | ', $nameAddressParts), 0, 1, 'L', false);

        $contactLineParts = [];
        if (!empty($contactParts)) {
            $contactLineParts[] = implode(' | ', $contactParts);
        }

        $this->SetX($leftMargin);
        $this->Cell($usableWidth, 4, implode(' | ', $contactLineParts), 0, 1, 'L', false);

        $this->SetX($leftMargin);
        $this->Cell($usableWidth, 4, implode(' | ', $registerParts), 0, 0, 'L', false);
    }
}
