<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

if (!class_exists('TCPDF')) {
    throw new RuntimeException('TCPDF wurde über Composer nicht geladen.');
}

if (!class_exists(\setasign\Fpdi\Tcpdf\Fpdi::class)) {
    throw new RuntimeException('FPDI wurde über Composer nicht geladen.');
}

/**
 * TCPDF-Erweiterung für die Einladungs-PDF mit Organisationsfußzeile. Bewusst im globalen
 * Namespace, da TCPDF selbst nicht namespaced ist. Erbt von Fpdi, um PDF-Anhänge
 * importieren zu können.
 */
class MeetingInvitationPdf extends \setasign\Fpdi\Tcpdf\Fpdi
{
    private ?object $organization = null;
    private array $boardOfDirectors = [];
    private bool $isAttachmentPage = false;

    public function setAttachmentPage(bool $isAttachmentPage): void
    {
        $this->isAttachmentPage = $isAttachmentPage;
    }

    /**
     * Dezentes diagonales Wasserzeichen "Anhang" hinter dem Seiteninhalt.
     * Transparenz stellt sicher, dass der Anhang-Inhalt nicht überdeckt wird.
     */
    public function watermarkAttachment(): void
    {
        $pageWidth = $this->getPageWidth();
        $pageHeight = $this->getPageHeight();

        $this->SetAlpha(0.08);
        $this->SetFont('helvetica', 'B', 80);
        $this->SetTextColor(150, 150, 150);
        $this->StartTransform();
        $this->Rotate(45, $pageWidth / 2, $pageHeight / 2);
        $this->Text($pageWidth / 2 - 70, $pageHeight / 2 - 15, 'Anhang');
        $this->StopTransform();
        $this->SetAlpha(1);
    }

    public function setOrganization(?object $organization): void
    {
        $this->organization = $organization;
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
        if ($this->isAttachmentPage) {
            return;
        }

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
        $name1 = trim((string)($this->organization->name1 ?? ''));
        $name2 = trim((string)($this->organization->name2 ?? ''));
        if ($name1 !== '') {
            $nameParts[] = $name1;
        }
        if ($name2 !== '') {
            $nameParts[] = $name2;
        }

        $street = trim((string)($this->organization->street ?? ''));
        $city = trim((string)($this->organization->zipCode ?? '') . ' ' . (string)($this->organization->city ?? ''));
        $country = trim((string)($this->organization->country ?? ''));

        $addressParts = array_filter([$street, $city, $country], static fn($value) => $value !== '');
        $addressLine = implode(' | ', $addressParts);

        $contactParts = [];
        $phone = trim((string)($this->organization->phone ?? ''));
        $fax = trim((string)($this->organization->fax ?? ''));
        $email = trim((string)($this->organization->email ?? ''));
        $web = trim((string)($this->organization->homepage ?? ''));

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

        $registryCourt = trim((string)($this->organization->registryCourt ?? ''));
        $registryEntry = trim((string)($this->organization->registryEntry ?? ''));

        $boardNames = [];
        foreach ($this->boardOfDirectors as $member) {
            $boardName = trim((string)($member->firstName ?? '') . ' ' . (string)($member->lastName ?? ''));
            if ($boardName !== '') {
                $boardNames[] = $boardName;
            }
        }

        $registerParts = [];
        if ($registryCourt !== '') {
            $registerParts[] = 'Registergericht: ' . $registryCourt;
        }
        if ($registryEntry !== '') {
            $registerParts[] = 'Eintrag: ' . $registryEntry;
        }

        if (!empty($boardNames)) {
            $registerParts[] = 'Vorstand: ' . implode(', ', $boardNames);
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
