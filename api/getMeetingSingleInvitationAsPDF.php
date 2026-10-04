<?php

require_once __DIR__ . '/../src/init.php';

use Src\Meeting\InvitationPdfBuilder;

if (!$auth->isLoggedIn()) {
    $redirect->to('../login.php');
}

if (!isset($_GET['meetingId'])) {
    $redirect->back();
}

$meeting = $meetingRepository->findById((int)($_GET['meetingId']));

if (!$meeting || !$meeting->isActive) {
    $flash->set('errorMsg', 'Diese Besprechung existiert nicht oder wurde gelöscht');
    $flash->set('errorColor', 'red');
    $flash->set('errorCode', '404');
    $redirect->to('../error.php');
}

$participantId = (int)($_GET['participantId'] ?? 0);
$participant = $meetingParticipantRepository->findById($participantId);

if (!$participant || $participant->meeting !== $meeting->id) {
    $flash->set('errorMsg', 'Teilnehmer nicht gefunden oder gehört nicht zu dieser Besprechung');
    $flash->set('errorColor', 'red');
    $flash->set('errorCode', '404');
    $redirect->to('../error.php');
}

[$pdf, $filename] = InvitationPdfBuilder::build($app, $meeting, $participant);

$pdf->Output($filename, 'I');
