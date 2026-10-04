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

$meetingId = $input['meetingId'] ?? null;
$participantId = $input['participantId'] ?? null;
$status = $input['status'] ?? null;

if (!$participantId || !$status) {
    http_response_code(400);
    echo json_encode(['error' => 'Participant ID and status are required']);
    exit;
}

$participant = $meetingParticipantRepository->findById((int)$participantId);

if (!$participant) {
    http_response_code(404);
    echo json_encode(['error' => 'Participant not found']);
    exit;
}

switch ($status) {
    case 'present':
        $participant->isPresent = true;
        $participant->isExcused = false;
        $participant->isAbsent = false;
        break;
    case 'excused':
        $participant->isPresent = false;
        $participant->isExcused = true;
        $participant->isAbsent = false;
        break;
    case 'absent':
        $participant->isPresent = false;
        $participant->isExcused = false;
        $participant->isAbsent = true;
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        exit;
}

$meetingParticipantRepository->updateParticipant($meetingId, $participant);

http_response_code(200);
echo json_encode(['message' => 'Participant status updated successfully']);
