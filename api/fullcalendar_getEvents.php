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

$meetings = $meetingRepository->findAll();

$events = array_map(function ($meeting) {
    return [
        'id' => $meeting->id,
        'title' => $meeting->title,
        'start' => date('Y-m-d\TH:i:s', strtotime($meeting->date . ' ' . $meeting->startTime)),
        'end' => date('Y-m-d\TH:i:s', strtotime($meeting->date . ' ' . $meeting->endTime)),
        'color' => '#007bff', // Standardfarbe für alle Besprechungen
    ];
}, $meetings);

echo json_encode($events);
