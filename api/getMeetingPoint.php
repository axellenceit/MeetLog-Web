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

$meetingPoint = $meetingPointRepository->findById((int)$meetingPointId);

if (!$meetingPoint) {
    http_response_code(404);
    echo json_encode(['error' => 'Meeting point not found']);
    exit;
}

echo json_encode([
    'id' => $meetingPoint->id,
    'meetingId' => $meetingPoint->meetingId,
    'position' => $meetingPoint->position,
    'parentPosition' => $meetingPoint->parentPosition,
    'title' => $meetingPoint->title,
    'description' => $meetingPoint->description,
    'type' => $meetingPoint->type,
    'result' => $meetingPoint->result,
    'resultApproval' => $meetingPoint->resultApproval,
    'resultRejection' => $meetingPoint->resultRejection,
    'resultAbstention' => $meetingPoint->resultAbstention,
    'isActive' => $meetingPoint->isActive,
]);
