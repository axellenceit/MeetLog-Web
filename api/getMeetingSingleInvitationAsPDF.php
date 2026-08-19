<?php

require_once __DIR__ . '/../src/init.php';

use Src\Besprechung\InvitationPdfBuilder;

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
    $flash->set('errorCode', '404');
    $redirect->to('../error.php');
}

$participantId = (int)($_GET['participantId'] ?? 0);
$teilnehmer = $besprechungsteilnehmerRepository->findById($participantId);

if (!$teilnehmer || $teilnehmer->besprechung !== $besprechung->id) {
    $flash->set('errorMsg', 'Teilnehmer nicht gefunden oder gehört nicht zu dieser Besprechung');
    $flash->set('errorColor', 'red');
    $flash->set('errorCode', '404');
    $redirect->to('../error.php');
}

[$pdf, $filename] = InvitationPdfBuilder::build($app, $besprechung, $teilnehmer);

$pdf->Output($filename, 'I');
