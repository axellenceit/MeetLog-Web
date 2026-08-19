<?php

declare(strict_types=1);

ob_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../src/init.php';

/*if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}*/

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input)) {
    parse_str($rawInput, $input);
}

$besprechungId = $input['besprechungId'] ?? null;
$participantId = $input['participantId'] ?? null;
$status = $input['status'] ?? null;

if (!$participantId || !$status) {
    http_response_code(400);
    echo json_encode(['error' => 'Participant ID and status are required']);
    exit;
}

$participant = $besprechungsteilnehmerRepository->findById((int)$participantId);

if (!$participant) {
    http_response_code(404);
    echo json_encode(['error' => 'Participant not found']);
    exit;
}

switch ($status) {
    case 'present':
        $participant->istAnwesend = true;
        $participant->istEntschuldigt = false;
        $participant->istAbwesend = false;
        break;
    case 'excused':
        $participant->istAnwesend = false;
        $participant->istEntschuldigt = true;
        $participant->istAbwesend = false;
        break;
    case 'absent':
        $participant->istAnwesend = false;
        $participant->istEntschuldigt = false;
        $participant->istAbwesend = true;
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        exit;
}

$besprechungsteilnehmerRepository->updateTeilnehmer($besprechungId, $participant);

http_response_code(200);
echo json_encode(['message' => 'Participant status updated successfully']);
