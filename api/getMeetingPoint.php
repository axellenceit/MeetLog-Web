<?php

declare(strict_types=1);

ob_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../src/init.php';

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$meetingPointId = $_GET['id'] ?? null;

if (!$meetingPointId) {
    http_response_code(400);
    echo json_encode(['error' => 'Meeting point ID is required']);
    exit;
}

$meetingPoint = $besprechungspunktRepository->findById((int)$meetingPointId);

if (!$meetingPoint) {
    http_response_code(404);
    echo json_encode(['error' => 'Meeting point not found']);
    exit;
}

echo json_encode([
    'id' => $meetingPoint->id,
    'besprechungId' => $meetingPoint->besprechungId,
    'position' => $meetingPoint->position,
    'elternPosition' => $meetingPoint->elternPosition,
    'titel' => $meetingPoint->titel,
    'beschreibung' => $meetingPoint->beschreibung,
    'typ' => $meetingPoint->typ,
    'ergebnis' => $meetingPoint->ergebnis,
    'ergebnis_zustimmung' => $meetingPoint->ergebnis_zustimmung,
    'ergebnis_ablehnung' => $meetingPoint->ergebnis_ablehnung,
    'ergebnis_enthaltung' => $meetingPoint->ergebnis_enthaltung,
    'istAktiviert' => $meetingPoint->istAktiviert,
]);