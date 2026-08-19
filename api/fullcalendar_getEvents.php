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

$besprechungen = $besprechungRepository->findAll();

$events = array_map(function ($besprechung) {
    return [
        'id' => $besprechung->id,
        'title' => $besprechung->titel,
        'start' => date('Y-m-d\TH:i:s', strtotime($besprechung->datum . ' ' . $besprechung->beginn)),
        'end' => date('Y-m-d\TH:i:s', strtotime($besprechung->datum . ' ' . $besprechung->ende)),
        'color' => '#007bff', // Standardfarbe für alle Besprechungen
    ];
}, $besprechungen);

echo json_encode($events);
