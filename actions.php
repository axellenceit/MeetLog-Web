<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/version.php';

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

use Src\User\User;
use Src\Organization\OrganizationMember;
use Src\Organization\OrganizationFunction;
use Src\Person\Person;
use Src\Meeting\Meeting;
use Src\Meeting\MeetingAttachment;
use Src\Meeting\InvitationPdfBuilder;
use Src\Task\Task;
use Src\Log\Log;

$action = $_GET['action'] ?? $_POST['action'] ?? null;

function buildFeedbackRedirectUrl(string $status): string
{
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    $parts = parse_url($referer);

    $path = $parts['path'] ?? 'index.php';
    $query = [];

    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    $query['feedback_issue'] = $status;

    return $path . '?' . http_build_query($query);
}

switch ($action) {
    case 'logout':
        $auth->logout();
        $redirect->to('login.php');
        break;

    case 'deleteProfilePhoto':
        $result = $userRepository->deleteProfilePhoto($session->get('id'));

        if (!$result) {
            $flash->addToast('Foto konnte nicht gelöscht werden', 'danger');
            $redirect->back();
        }

        $flash->addToast('Foto erfolgreich gelöscht', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Profilfoto zu ' . $_SESSION['id'] . ' gelöscht',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'uploadProfilePhoto':

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $flash->addToast('Bitte Foto auswählen', 'danger');
            $redirect->back();
        }

        $file = $_FILES['file'];
        $maxSize = 2 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            $flash->addToast('Datei zu groß. Maximal 2 MB', 'danger');
            $redirect->back();
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedMimeTypes[$mimeType])) {
            $flash->addToast('Nicht zulässiges Dateiformat', 'danger');
            $redirect->back();
        }

        $extension = $allowedMimeTypes[$mimeType];

        $uploadDir = __DIR__ . '/upload/user/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $newFilename = (string) 'user_' . $session->get('id') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = (string) $uploadDir . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $flash->addToast('Hochladen fehlgeschlagen', 'danger');
            $redirect->back();
        }

        $relativePath = (string) './upload/user/' . $newFilename;

        $userRepository->addProfilePhoto($session->get('id'), $relativePath);

        $flash->addToast('Foto erfolgreich hochgeladen', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Profilfoto zu ' . $_SESSION['id'] . ' hochgeladen',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'editMeeting':
        $meetingId = $_POST['editMeetingId'] ?? null;

        if (!$meetingId) {
            $flash->addToast('Meeting ID fehlt', 'danger');
            $redirect->back();
        }

        $meeting = $meetingRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
            $redirect->back();
        }

        $meeting->title = $_POST['editMeetingTitle'] ?? $meeting->title;
        $meeting->location = $_POST['editMeetingLocation'] ?? $meeting->location;
        $meeting->date = $_POST['editMeetingDate'] ?? $meeting->date;
        $meeting->startTime = $_POST['editMeetingStartTime'] ?? $meeting->startTime;
        $meeting->endTime = $_POST['editMeetingEndTime'] ?? $meeting->endTime;

        $meetingRepository->update($meeting);

        $flash->addToast('Meeting erfolgreich geändert', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Meeting ' . $meetingId . ' geändert',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'changeMeetingStatus':
        $meetingId = $_POST['changeMeetingStatusId'] ?? null;
        $status = $_POST['changeMeetingStatusStatus'] ?? null;

        if (!$meetingId || !$status) {
            $flash->addToast('Meeting ID oder Status fehlt', 'danger');
            $redirect->back();
        }

        $meeting = $meetingRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
            $redirect->back();
        }

        if (!in_array($status, ['planned', 'active', 'finished'])) {
            $flash->addToast('Ungültiger Status', 'danger');
            $redirect->back();
        }

        $meeting->status = $status;
        $meetingRepository->update($meeting);

        $flash->addToast('Meeting erfolgreich geändert', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Meeting ' . $meetingId . ' Status geändert zu ' . $status,
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'publishMeeting':
        $meetingId = $_POST['publishMeetingId'] ?? null;

        if (!$meetingId) {
            $flash->addToast('Meeting ID fehlt', 'danger');
            $redirect->back();
        }

        $meeting = $meetingRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
            $redirect->back();
        }

        if ($meeting->status !== 'finished') {
            $flash->addToast('Nur Meetings mit Status "beendet" können veröffentlicht werden', 'danger');
            $redirect->back();
        }

        $meeting->isPublished = true;
        $meeting->publicCode = bin2hex(random_bytes(8));
        $meetingRepository->update($meeting);

        $flash->addToast('Meeting erfolgreich veröffentlicht', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Meeting ' . $meetingId . ' veröffentlicht',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'unpublishMeeting':
        $meetingId = $_POST['unpublishMeetingId'] ?? null;

        if (!$meetingId) {
            $flash->addToast('Meeting ID fehlt', 'danger');
            $redirect->back();
        }

        $meeting = $meetingRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
            $redirect->back();
        }

        $meeting->isPublished = false;
        $meeting->publicCode = null;
        $meetingRepository->update($meeting);

        $flash->addToast('Meeting erfolgreich zurückgezogen', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Meeting ' . $meetingId . ' zurückgezogen',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'deleteMeeting':
        $meetingId = $_POST['deleteMeetingId'] ?? null;

        if (!$meetingId) {
            $flash->addToast('Meeting ID fehlt', 'danger');
            $redirect->back();
        }

        $meeting = $meetingRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
            $redirect->back();
        }

        $meeting->isActive = false;

        $meetingRepository->update($meeting);

        $flash->addToast('Meeting erfolgreich gelöscht', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Meeting ' . $meetingId . ' gelöscht',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'addMeetingPoint':
        $meetingId = $_POST['newMeetingPointId'] ?? null;

        if (!$meetingId) {
            $flash->addToast('Meeting ID fehlt', 'danger');
            $redirect->back();
        }

        $meeting = $meetingRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
            $redirect->back();
        }

        $parentId = (int)($_POST['newMeetingPointParent'] ?? 0);
        $parentPosition = 0;

        if ($parentId > 0) {
            $parentMeetingPoint = $meetingPointRepository->findById($parentId);

            if ($parentMeetingPoint && $parentMeetingPoint->meetingId === (int)$meetingId) {
                $parentPosition = $parentMeetingPoint->position;
            }
        }

        $newMeetingPoint = new \Src\Meeting\MeetingPoint();
        $newMeetingPoint->meetingId = (int)$meetingId;
        $newMeetingPoint->position = $meetingPointRepository->findNextPosition((int)$meetingId, $parentPosition);
        $newMeetingPoint->title = $_POST['newMeetingPointTitle'] ?? '';
        $newMeetingPoint->description = $_POST['newMeetingPointDescription'] ?? '';
        $newMeetingPoint->type = $_POST['newMeetingPointType'] ?? '';
        $newMeetingPoint->parentPosition = $parentPosition;
        $newMeetingPoint->result = $_POST['newMeetingPointResult'] ?? '';

        $result = $meetingPointRepository->insert($newMeetingPoint);

        if (!$result) {
            $flash->addToast('Fehler beim Hinzufügen des Besprechungspunkts', 'danger');
            $redirect->back();
        }

        $flash->addToast('Besprechungspunkt erfolgreich hinzugefügt', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Bei Meeting ' . $meetingId . ' Besprechungspunkt hinzugefügt',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'editMeetingPoint':
        $meetingPointId = $_POST['editMeetingPointId'] ?? null;

        if (!$meetingPointId) {
            $flash->addToast('Meeting Point ID fehlt', 'danger');
            $redirect->back();
        }

        $meetingPoint = $meetingPointRepository->findById((int)$meetingPointId);

        if (!$meetingPoint) {
            $flash->addToast('Meeting Point nicht gefunden', 'danger');
            $redirect->back();
        }

        $meetingPoint->description = $_POST['editMeetingPointDescription'] ?? $meetingPoint->description;
        $meetingPoint->type = $_POST['editMeetingPointType'] ?? $meetingPoint->type;
        $meetingPoint->parentPosition = (int)($_POST['editMeetingPointParent'] ?? $meetingPoint->parentPosition);
        $meetingPoint->result = $_POST['editMeetingPointResult'] ?? $meetingPoint->result;

        $meetingPoint->resultApproval = (int)($_POST['editMeetingPointVotingYes'] ?? $meetingPoint->resultApproval);
        $meetingPoint->resultRejection = (int)($_POST['editMeetingPointVotingNo'] ?? $meetingPoint->resultRejection);
        $meetingPoint->resultAbstention = (int)($_POST['editMeetingPointVotingAbstain'] ?? $meetingPoint->resultAbstention);

        $result = $meetingPointRepository->update($meetingPoint);

        $flash->addToast('Besprechungspunkt erfolgreich bearbeitet', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Besprechungspunkt ' . $meetingPointId . ' bearbeitet',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'addMeetingParticipants':
        $meetingId = (int) ($_POST['meetingId'] ?? 0);

        $meeting = $meetingRepository->findById($meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
            $redirect->back();
        }

        $participantIds = array_map('intval', (array) ($_POST['participants'] ?? []));

        if (empty($participantIds)) {
            $flash->addToast('Keine Teilnehmer ausgewählt', 'danger');
            $redirect->back();
        }

        foreach ($participantIds as $personId) {
            if ($personId <= 0) {
                continue;
            }

            $meetingParticipantRepository->addParticipant($meetingId, new \Src\Meeting\MeetingParticipant(
                meeting: $meetingId,
                person: $personId,
            ));
        }

        $flash->addToast('Teilnehmer erfolgreich hinzugefügt', 'success');
        $redirect->back();
        break;

    case 'meetingChairman':
        $meetingId = $_GET['meetingId'] ?? null;
        $participantId = $_GET['participantId'] ?? null;

        if (!$meetingId || !$participantId) {
            $flash->addToast('Meeting ID oder Teilnehmer ID fehlt', 'danger');
            $redirect->back();
        }

        $meeting = $meetingRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
            $redirect->back();
        }

        $participant = $meetingParticipantRepository->findById((int)$participantId);

        if (!$participant) {
            $flash->addToast('Teilnehmer nicht gefunden', 'danger');
            $redirect->back();
        }

        // Setze den aktuellen Vorsitzenden auf false
        $currentChairman = $meetingParticipantRepository->findChairmanByMeetingId((int)$meetingId);
        if ($currentChairman) {
            $currentChairman->isChair = false;
            $meetingParticipantRepository->updateParticipant((int) $meetingId, $currentChairman);
        }

        // Setze den neuen Vorsitzenden auf true
        $participant->isChair = true;
        $meetingParticipantRepository->updateParticipant((int) $meetingId, $participant);

        $redirect->back();
        break;

    case 'meetingSecretary':
        $meetingId = $_GET['meetingId'] ?? null;
        $participantId = $_GET['participantId'] ?? null;

        if (!$meetingId || !$participantId) {
            $flash->addToast('Meeting ID oder Teilnehmer ID fehlt', 'danger');
            $redirect->back();
        }

        $meeting = $meetingRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
            $redirect->back();
        }

        $participant = $meetingParticipantRepository->findById((int)$participantId);

        if (!$participant) {
            $flash->addToast('Teilnehmer nicht gefunden', 'danger');
            $redirect->back();
        }

        // Setze den aktuellen Protokollführer auf false
        $currentSecretary = $meetingParticipantRepository->findSecretaryByMeetingId((int)$meetingId);
        if ($currentSecretary) {
            $currentSecretary->isMinuteTaker = false;
            $meetingParticipantRepository->updateParticipant((int) $meetingId, $currentSecretary);
        }

        // Setze den neuen Protokollführer auf true
        $participant->isMinuteTaker = true;
        $meetingParticipantRepository->updateParticipant((int) $meetingId, $participant);

        $redirect->back();
        break;

    case 'meetingSign':
        $meetingId = $_GET['meetingId'] ?? null;
        $participantId = $_GET['participantId'] ?? null;

        if (!$meetingId || !$participantId) {
            $flash->addToast('Meeting ID oder Teilnehmer ID fehlt', 'danger');
            $redirect->back();
        }

        $meeting = $meetingRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
            $redirect->back();
        }

        $participant = $meetingParticipantRepository->findById((int)$participantId);

        if (!$participant) {
            $flash->addToast('Teilnehmer nicht gefunden', 'danger');
            $redirect->back();
        }

        // Toggle the signing authorization
        $participant->maySign = !$participant->maySign;
        $meetingParticipantRepository->updateParticipant((int) $meetingId, $participant);

        $redirect->back();
        break;

    case 'sendInvitations':
        $meetingId = $_POST['sendInvitationMeetingId'] ?? null;
        $participantIds = $_POST['sendInvitationParticipantIds'] ?? [];

        if (!$meetingId) {
            $flash->addToast('Meeting ID fehlt', 'danger');
        }

        $meeting = $meetingRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
        }

        if (!is_array($participantIds) || empty($participantIds)) {
            $flash->addToast('Bitte mindestens einen Teilnehmer auswählen', 'danger');
            $redirect->back();
        }

        $failedCount = 0;
        $missingFallbackRecipient = false;

        foreach ($participantIds as $participantId) {
            $participant = $meetingParticipantRepository->findById((int)$participantId);

            if (!$participant || $participant->meeting !== $meeting->id) {
                $failedCount++;
                continue;
            }

            $pdfContent = InvitationPdfBuilder::render($app, $meeting, $participant);
            $attachmentName = 'Einladung_' . str_replace('.', '', str_replace(' ', '_', $meeting->title)) . '.pdf';

            $recipientName = trim((string)$participant->firstName . ' ' . (string)$participant->lastName);
            $subject = $translator->t('meetings.participants.invitationMail.subject') . ': ' . $meeting->title;

            $body = '<p>' . $translator->t('meetings.participants.invitationMail.greeting') . '</p>'
                . '<p>' . sprintf($translator->t('meetings.participants.invitationMail.body'), htmlspecialchars($meeting->title, ENT_QUOTES, 'UTF-8')) . '</p>'
                . '<p>' . $translator->t('meetings.participants.invitationMail.closing') . '</p>'
                . $mail->imprint($meeting->organizationId);

            if (empty($participant->mail) || $participant->mail === null) {
                // Wenn die E-Mail-Adresse leer ist, wird die Einladung an den ausführenden Benutzer gesendet, damit er die Einladung postalisch verschicken kann
                $currentUser = $userRepository->findById((int)$session->get('id'));
                $fallbackMail = trim((string)($currentUser->mail ?? ''));

                if (!filter_var($fallbackMail, FILTER_VALIDATE_EMAIL)) {
                    $failedCount++;
                    $missingFallbackRecipient = true;
                    continue;
                }

                $sent = $mail->send($fallbackMail, 'Einladung postalisch verschicken: ' . $meeting->title, $body, [
                    ['name' => $attachmentName, 'content' => $pdfContent],
                ]);
            } else {
                $sent = $mail->send($participant->mail, $subject, $body, [
                    ['name' => $attachmentName, 'content' => $pdfContent],
                ]);
            }

            if ($sent) {
                $participant->isInvited = true;
                $meetingParticipantRepository->updateParticipant((int)$meeting->id, $participant);
                $meeting->status = $meeting::STATUS_INVITED;
                $meetingRepository->update($meeting);
            } else {
                $failedCount++;
            }
        }

        if ($failedCount > 0) {
            $errorMessage = 'Einladung konnte an ' . $failedCount . ' Teilnehmer nicht gesendet werden';
            if ($missingFallbackRecipient) {
                $errorMessage .= '. Ihre Benutzer-E-Mail-Adresse fehlt oder ist ungültig';
            }
            $flash->addToast($errorMessage, 'danger');
        }

        $redirect->back();
        break;

    case 'uploadMeetingAttachment':
        $meetingId = $_POST['meetingId'] ?? null;

        if (!$meetingId) {
            $flash->addToast('Meeting ID fehlt', 'danger');
            $redirect->back();
        }

        $meeting = $meetingRepository->findById((int)$meetingId);

        if (!$meeting || !$meeting->isActive) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
            $redirect->back();
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $flash->addToast('Bitte Datei auswählen', 'danger');
            $redirect->back();
        }

        $file = $_FILES['file'];
        $maxSize = 20 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            $flash->addToast('Datei zu groß. Maximal 20 MB', 'danger');
            $redirect->back();
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string)$finfo->file($file['tmp_name']);
        $originalName = basename((string)$file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Nur PDF-Dateien zulässig
        if ($mimeType !== 'application/pdf' || $extension !== 'pdf') {
            $flash->addToast('Nicht zulässiges Dateiformat. Erlaubt sind nur PDF-Dateien', 'danger');
            $redirect->back();
        }

        $extension = 'pdf';

        $uploadDir = __DIR__ . '/upload/meeting/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $newFilename = 'meeting_' . (int)$meetingId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $uploadDir . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $flash->addToast('Hochladen fehlgeschlagen', 'danger');
            $redirect->back();
        }

        $attachment = new MeetingAttachment(
            meeting: (int)$meetingId,
            fileName: $newFilename,
            originalFileName: $originalName,
            filePath: './upload/meeting/' . $newFilename,
            mimeType: $mimeType,
            fileSize: (int)$file['size'],
            description: trim((string)($_POST['description'] ?? '')) ?: null,
            printWithInvitation: isset($_POST['printWithInvitation']),
            printWithMinutes: isset($_POST['printWithMinutes']),
            uploadedBy: (int)$session->get('id'),
        );

        $attachmentId = $meetingAttachmentRepository->create($attachment);

        $flash->addToast('Anhang erfolgreich hochgeladen', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Anhang ' . $attachmentId . ' zu Meeting ' . $meetingId . ' hochgeladen',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'deleteMeetingAttachment':
        $attachmentId = (int)($_GET['attachmentId'] ?? $_POST['attachmentId'] ?? 0);
        $meetingId = (int)($_GET['meetingId'] ?? $_POST['meetingId'] ?? 0);

        $attachment = $meetingAttachmentRepository->findById($attachmentId);

        if (!$attachment || (int)$attachment->meeting !== $meetingId) {
            $flash->addToast('Anhang nicht gefunden', 'danger');
            $redirect->back();
        }

        $meetingAttachmentRepository->delete($attachmentId);

        // Physische Datei entfernen
        if (!empty($attachment->filePath)) {
            $absolutePath = __DIR__ . '/' . ltrim((string)$attachment->filePath, './');
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
        }

        $flash->addToast('Anhang erfolgreich gelöscht', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Anhang ' . $attachmentId . ' zu Meeting ' . $meetingId . ' gelöscht',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'updateMeetingAttachmentFlags':
        $attachmentId = (int)($_POST['attachmentId'] ?? 0);
        $meetingId = (int)($_POST['meetingId'] ?? 0);

        $attachment = $meetingAttachmentRepository->findById($attachmentId);

        if (!$attachment || (int)$attachment->meeting !== $meetingId) {
            $flash->addToast('Anhang nicht gefunden', 'danger');
            $redirect->back();
        }

        $meetingAttachmentRepository->updatePrintFlags(
            $attachmentId,
            isset($_POST['printWithInvitation']),
            isset($_POST['printWithMinutes'])
        );

        $flash->addToast('Druck-Einstellungen gespeichert', 'success');

        $redirect->back();
        break;

    case 'updateOrganization':
        $orgId = $_POST['organizationId'] ?? null;

        if (!$orgId) {
            $flash->addToast('OrganisationsID fehlt', 'danger');
            $redirect->back();
        }

        $org = $organizationRepository->findById((int) $orgId);

        if (!$org) {
            $flash->addToast('Organisation nicht gefunden', 'danger');
            $redirect->back();
        }

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo'];
            $maxSize = 2 * 1024 * 1024;

            if ($file['size'] > $maxSize) {
                $flash->addToast('Datei zu groß. Maximal 2 MB', 'danger');
                $redirect->back();
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];

            if (!isset($allowedMimeTypes[$mimeType])) {
                $flash->addToast('Nicht zulässiges Dateiformat', 'danger');
                $redirect->back();
            }

            $extension = $allowedMimeTypes[$mimeType];

            $uploadDir = __DIR__ . '/upload/organization/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $newFilename = (string) 'organization_' . $org->id . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $targetPath = (string) $uploadDir . $newFilename;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $flash->addToast('Hochladen fehlgeschlagen', 'danger');
                $redirect->back();
            }

            $relativePath = (string) '/upload/organization/' . $newFilename;

            $org->logo = $relativePath;
        }

        $org->name1 = $_POST['name1'] ?? $org->name1;
        $org->name2 = $_POST['name2'] ?? $org->name2;
        $org->homepage = $_POST['homepage'] ?? $org->homepage;
        $org->email = $_POST['email'] ?? $org->email;
        $org->phone = $_POST['phone'] ?? $org->phone;
        $org->fax = $_POST['fax'] ?? $org->fax;
        $org->street = $_POST['street'] ?? $org->street;
        $org->zipCode = $_POST['zipCode'] ?? $org->zipCode;
        $org->city = $_POST['city'] ?? $org->city;
        $org->country = $_POST['country'] ?? $org->country;
        $org->registryCourt = $_POST['registryCourt'] ?? $org->registryCourt;
        $org->registryEntry = $_POST['registryEntry'] ?? $org->registryEntry;

        $organizationRepository->update($org);

        $redirect->back();

        break;

    case 'saveConfig':
        $data = $_POST ?? [];

        if (empty($data)) {
            $flash->addToast('Keine Daten zum speichern gefunden', 'danger');
            $redirect->back();
        }

        foreach ($data as $name => $value) {
            if ($name === 'updateConfigButton') {
                continue;
            }

            $parts = explode('_', $name, 3);

            if (count($parts) !== 3 || $parts[0] !== 'update') {
                continue;
            }

            $section = $parts[1];
            $key = $parts[2];

            $config->set($section, $key, $value);
        }

        $flash->addToast('Konfiguration erfolgreich gespeichert', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Konfiguration gespeichert',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $config->save();
        $config->reload();

        $redirect->back();

        break;

    case 'testConfig':
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        $type = $_POST['type'] ?? '';

        if ($type === 'mail') {
            $host   = trim($_POST['update_mail_server'] ?? '');
            $port   = (int)($_POST['update_mail_port'] ?? 587);
            $user   = trim($_POST['update_mail_user'] ?? '');
            $pass   = (string)($_POST['update_mail_pass'] ?? '');
            $secure = trim($_POST['update_mail_usessl'] ?? 'tls');

            if ($host === '' || $port <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Mail-Host und Port sind erforderlich'
                ]);
                exit;
            }

            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $host;
                $mail->Port = $port;
                $mail->Timeout = 5;

                if ($secure === 'ssl') {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($secure === 'tls') {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                } else {
                    $mail->SMTPSecure = false;
                }

                if ($user !== '') {
                    $mail->SMTPAuth = true;
                    $mail->Username = $user;
                    $mail->Password = $pass;
                } else {
                    $mail->SMTPAuth = false;
                }

                $ok = $mail->smtpConnect();

                if ($ok) {
                    $mail->smtpClose();
                    echo json_encode([
                        'success' => true,
                        'message' => 'SMTP-Verbindung erfolgreich'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'SMTP-Verbindung fehlgeschlagen'
                    ]);
                }
            } catch (\Throwable $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'SMTP-Test fehlgeschlagen: ' . $e->getMessage()
                ]);
            }
            exit;
        }

        if ($type === 'database') {
            $host    = trim($_POST['update_database_host'] ?? '');
            $port    = trim($_POST['update_database_port'] ?? '3306');
            $dbname  = trim($_POST['update_database_name'] ?? '');
            $user    = trim($_POST['update_database_user'] ?? '');
            $pass    = (string)($_POST['update_database_pass'] ?? '');
            $charset = trim($_POST['update_database_charset'] ?? 'utf8mb4');

            if ($host === '' || $port === '' || $dbname === '' || $user === '') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Host, Port, Datenbankname und Benutzer sind erforderlich'
                ]);
                exit;
            }

            if (str_contains($host, ';') || str_contains($dbname, ';')) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Ungültige Zeichen in Host oder Datenbankname'
                ]);
                exit;
            }

            try {
                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Datenbankverbindung erfolgreich'
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage()
                ]);
            }
            exit;
        }

        echo json_encode([
            'success' => false,
            'message' => 'Ungültiger Testtyp'
        ]);

        break;

    case 'create_member':
        $orgId = $_POST['organization_id'] ?? null;
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $zipCode = trim($_POST['zipCode'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['mail'] ?? '');
        $pass = trim($_POST['password'] ?? '');
        $functionId = $_POST['function_id'] ?? null;
        $countryId = ($country !== '' && ctype_digit($country)) ? (int) $country : null;

        # Wenn eine E-Mail-Adresse angegeben wurde, dann soll auch ein Benutzerkonto angelegt werden

        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $flash->addToast('Ungültige E-Mail-Adresse', 'danger');
                $redirect->back();
            }
        }

        $person = new Person(
            firstName: $firstName !== '' ? $firstName : null,
            lastName: $lastName !== '' ? $lastName : null,
            mail: $email !== '' ? $email : null,
            phone: $phone !== '' ? $phone : null,
            street: $street !== '' ? $street : null,
            zipCode: $zipCode !== '' ? $zipCode : null,
            city: $city !== '' ? $city : null,
            country: $countryId,
            isActive: 1,
            isDeleted: 0,
        );

        $personId = $personRepository->create($person);

        if (!$personId) {
            $flash->addToast('Fehler beim Erstellen der Personendaten', 'danger');
            $redirect->back();
        }

        if (!empty($email)) {
            if (empty($pass)) {
                $pass = bin2hex(random_bytes(12));
            }

            $user = new User();
            $user->person = (int) $personId;
            $user->firstName = $firstName;
            $user->lastName = $lastName;
            $user->mail = $email;
            $user->password = password_hash($pass, PASSWORD_DEFAULT);
            $user->defaultOrganization = $session->get('DefaultOrganization');
            $user->isActive = 1;

            $newUserId = $userRepository->addUser($user);

            if (!$newUserId) {
                $flash->addToast('Fehler beim Erstellen des Benutzerkontos', 'danger');
                $redirect->back();
            }
        }

        $member = new OrganizationMember();
        $member->organization = (int) $orgId;
        $member->person = (int) $personId;
        $member->user = isset($newUserId) ? (int) $newUserId : null;
        $member->function = isset($functionId) && $functionId !== '' ? (int) $functionId : null;
        $member->firstName = $firstName;
        $member->lastName = $lastName;
        $member->street = $street;
        $member->zipCode = $zipCode;
        $member->city = $city;
        $member->country = $countryId;
        $member->phone = $phone;
        $member->mail = $email;
        $member->isActive = 1;
        $member->isDeleted = 0;

        $result = (int) $organizationMemberRepository->addMember($member);

        if ($result) {
            if (isset($email) && isset($newUserId)) {
                $mail->sendWelcomeEmail($email, $firstName, $email, $pass);
            }
        } else {
            $flash->addToast('Fehler beim Erstellen des Mitglieds', 'danger');
        }

        $flash->addToast('Mitglied erfolgreich erstellt', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Mitglied erstellt',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'enable_member':
    case 'disable_member':

        $memberId = $_GET['member_id'] ?? null;

        if (!$memberId) {
            $flash->addToast('MitgliedsID fehlt', 'danger');
            $redirect->back();
        }

        $member = $organizationMemberRepository->findById((int) $memberId);

        if (!$member) {
            $flash->addToast('Mitglied nicht gefunden', 'danger');
            $redirect->back();
        }

        $user = $userRepository->findById($member->user);

        if ($user && $user->isAdmin && $action === 'disable_member') {
            $flash->addToast('Admin-Mitglied kann nicht deaktiviert werden', 'danger');
            $redirect->back();
        }

        $member->isActive = ($action === 'enable_member');

        $result = $organizationMemberRepository->update($member);

        if (!$result) {
            $flash->addToast('Fehler beim Aktualisieren des Mitgliedsstatus', 'danger');
        } else {
            $flash->addToast('Mitglied erfolgreich aktualisiert', 'success');
            $logEntry = new Log(
                id: null,
                userId: (int) $_SESSION['id'],
                action: 'Mitglied aktualisiert',
                severity: 'info',
                timestamp: date('Y-m-d H:i:s')
            );
            $logRepository->create($logEntry);
        }

        $redirect->back();
        break;

    case 'update_member':
        $memberId = $_POST['member_id'] ?? null;

        if (!$memberId) {
            $flash->addToast('MitgliedsID fehlt', 'danger');
            $redirect->back();
        }

        $member = $organizationMemberRepository->findById((int) $memberId);

        if (!$member) {
            $flash->addToast('Mitglied nicht gefunden', 'danger');
            $redirect->back();
        }

        $member->firstName = trim((string) ($_POST['firstName'] ?? (string) $member->firstName));
        $member->lastName = trim((string) ($_POST['lastName'] ?? (string) $member->lastName));
        $member->street = trim((string) ($_POST['street'] ?? (string) $member->street));
        $member->zipCode = trim((string) ($_POST['zipCode'] ?? (string) $member->zipCode));
        $member->city = trim((string) ($_POST['city'] ?? (string) $member->city));
        $postedCountry = trim((string) ($_POST['country'] ?? ''));
        $member->country = ($postedCountry !== '' && ctype_digit($postedCountry)) ? (int) $postedCountry : $member->country;
        $member->phone = trim((string) ($_POST['phone'] ?? (string) $member->phone));
        $member->mail = trim((string) ($_POST['mail'] ?? (string) $member->mail));

        $result = (int) $organizationMemberRepository->updateMember($member);

        if (!$result) {
            $flash->addToast('Fehler beim Aktualisieren des Mitglieds', 'danger');
            $redirect->back();
        }

        if ($member->user) {
            // Wenn das Mitglied ein Benutzerkonto hat, dann aktualisiere auch das Benutzerkonto
            $user = $userRepository->findById((int) $member->user);

            if ($user) {
                $user->person = $member->person;
                $user->firstName = (string) ($member->firstName ?? $user->firstName);
                $user->lastName = (string) ($member->lastName ?? $user->lastName);
                $newMail = trim((string) ($_POST['mail'] ?? ''));

                if ($newMail !== '' && filter_var($newMail, FILTER_VALIDATE_EMAIL)) {
                    $user->mail = $newMail;
                }

                $userRepository->updateUser($user);
            }
        }

        $flash->addToast('Mitglied erfolgreich aktualisiert', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Mitglied aktualisiert',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'delete_member':
        $memberId = $_GET['member_id'] ?? null;

        if (!$memberId) {
            $flash->addToast('MitgliedsID fehlt', 'danger');
        }

        $member = $organizationMemberRepository->findById((int) $memberId);

        if (!$member) {
            $flash->addToast('Mitglied nicht gefunden', 'danger');
        }

        $user = $userRepository->findById($member->user);

        if ($user && $user->isAdmin) {
            $flash->addToast('Admin-Mitglied kann nicht gelöscht werden', 'danger');
        }

        $member->isActive = 0;
        $member->isDeleted = 1;

        $mailaddress = '';
        $result = $organizationMemberRepository->updateMember($member);

        if ($result && $user) {
            $user->person = $member->person;
            $user->isActive = 0;
            $user->isDeleted = 1;

            $mailaddress = (string) $user->mail; // Für Lösch-Benachrichtigung sichern

            // Person/User anonymisieren
            $user->firstName = 'Anonym';
            $user->lastName = 'Anonym';
            $user->mail = 'anonym_' . bin2hex(random_bytes(8)) . '@example.com';
            $user->password = password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT);

            $result = $userRepository->updateUser($user);
        }

        if (!$result) {
            $flash->addToast('Fehler beim Aktualisieren des Mitgliedsstatus', 'danger');
        }

        if ($mailaddress !== '') {
            $mail->sendDeletionEmail($mailaddress, $member->organization);
        }

        $flash->addToast('Mitglied erfolgreich gelöscht', 'success');
        $logEntry = new Log(
            id: null,
            userId: (int) $_SESSION['id'],
            action: 'Mitglied gelöscht',
            severity: 'info',
            timestamp: date('Y-m-d H:i:s')
        );
        $logRepository->create($logEntry);

        $redirect->back();
        break;

    case 'update_profile':
        try {
            $firstName = trim((string) ($_POST['firstName'] ?? ''));
            $lastName = trim((string) ($_POST['lastName'] ?? ''));
            $mail = trim((string) ($_POST['mail'] ?? ''));
            $password = trim((string) ($_POST['password'] ?? ''));

            if ($firstName === '' || $lastName === '' || $mail === '') {
                $flash->addToast('Vorname, Nachname und E-Mail-Adresse sind erforderlich', 'danger');
                $redirect->back();
            }

            $user = $userRepository->findById((int) $session->get('id'));
            if ($user) {
                $user->firstName = $firstName;
                $user->lastName = $lastName;
                $user->mail = $mail;
                if ($password !== '') {
                    $user->password = password_hash($password, PASSWORD_DEFAULT);
                }
                $result = $userRepository->updateUser($user);
                if ($result) {
                    $flash->addToast('Profil erfolgreich aktualisiert \n Bitte melden Sie sich erneut an, um die Änderungen zu übernehmen.', 'success');
                    $auth->logout();
                } else {
                    $flash->addToast('Fehler beim Aktualisieren des Profils', 'danger');
                }
            }
            $redirect->back();
        } catch (Exception $e) {
            $flash->addToast('Fehler beim Aktualisieren des Profils', 'danger');
            $redirect->back();
        }
        break;

    case 'create_meeting':
        try {
            $organizationId = (int) ($session->get('DefaultOrganization') ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $date = trim((string) ($_POST['date'] ?? ''));
            $startTime = trim((string) ($_POST['startTime'] ?? ''));
            $endTime = trim((string) ($_POST['endTime'] ?? ''));
            $location = trim((string) ($_POST['location'] ?? ''));

            if ($organizationId <= 0 || $title === '' || $date === '') {
                $flash->addToast('Titel und Datum des Meetings sind erforderlich', 'danger');
                $redirect->back();
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))) {
                $flash->addToast('Ungültiges Datum', 'danger');
                $redirect->back();
            }

            // Validierung der Zeitangaben - Sollte noch --:-- enthalten sein, dann abbruch
            if ($startTime === '--:--') {
                $flash->addToast('Ungültige Startzeit', 'danger');
                $redirect->back();
            }
            if ($endTime === '--:--') {
                // Hier Hilfestellung, die Endezeit 30 Minuten nach Beginn zu setzen
                $endTime = date('H:i', strtotime($startTime . ' +30 minutes'));
            }

            $meetingId = $meetingRepository->create(new Meeting(
                organizationId: $organizationId,
                creatorUserId: (int) $session->get('id'),
                title: $title,
                date: $date,
                startTime: $startTime !== '' ? $startTime : null,
                endTime: $endTime !== '' ? $endTime : null,
                location: $location !== '' ? $location : null,
                status: Meeting::STATUS_PLANNED,
                isActive: true,
                isPublished: false,
            ));

            if ($meetingId <= 0) {
                $flash->addToast('Fehler beim Erstellen des Meetings', 'danger');
                $logEntry = new Log(
                    id: null,
                    userId: (int) $_SESSION['id'],
                    action: 'Fehler beim Erstellen des Meetings',
                    severity: 'error',
                    timestamp: date('Y-m-d H:i:s')
                );
                $logRepository->create($logEntry);
            }

            $participantIds = array_map('intval', (array) ($_POST['participants'] ?? []));
            foreach ($participantIds as $participantUserId) {
                if ($participantUserId <= 0) {
                    continue;
                }

                $meetingParticipantRepository->addParticipant($meetingId, new \Src\Meeting\MeetingParticipant(
                    meeting: $meetingId,
                    user: $participantUserId,
                ));
            }

            $flash->addToast('Meeting erfolgreich erstellt', 'success');
            $logEntry = new Log(
                id: null,
                userId: (int) $_SESSION['id'],
                action: 'Meeting erfolgreich erstellt',
                severity: 'info',
                timestamp: date('Y-m-d H:i:s')
            );
            $logRepository->create($logEntry);
            $redirect->to('meeting.php?id=' . $meetingId);
            break;
        } catch (Exception $e) {
            $flash->addToast('Fehler beim Erstellen des Meetings: ' . $e->getMessage(), 'danger');
            $logEntry = new Log(
                id: null,
                userId: (int) $_SESSION['id'],
                action: 'Fehler beim Erstellen des Meetings: ' . $e->getMessage(),
                severity: 'error',
                timestamp: date('Y-m-d H:i:s')
            );
            $logRepository->create($logEntry);
            $redirect->back();
        }

    case 'update_meeting':
        $meetingId = (int) ($_POST['meeting_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $date = trim((string) ($_POST['date'] ?? ''));
        $startTime = trim((string) ($_POST['start_time'] ?? ''));
        $endTime = trim((string) ($_POST['end_time'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? ''));
        $isActive = isset($_POST['is_active']) ? (bool) $_POST['is_active'] : true;
        $isPublished = isset($_POST['is_published']) ? (bool) $_POST['is_published'] : false;

        $meeting = $meetingRepository->findById($meetingId);

        if ($meeting === null) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
            $redirect->back();
            break;
        }

        $meeting->title = $title !== '' ? $title : $meeting->title;
        $meeting->date = $date !== '' ? $date : $meeting->date;
        $meeting->startTime = $startTime !== '' ? $startTime : $meeting->startTime;
        $meeting->endTime = $endTime !== '' ? $endTime : $meeting->endTime;
        $meeting->location = $location !== '' ? $location : $meeting->location;
        $meeting->status = $status !== '' ? $status : $meeting->status;
        $meeting->isActive = $isActive;
        $meeting->isPublished = $isPublished;

        if ($meetingRepository->update($meeting)) {
            $flash->addToast('Meeting erfolgreich aktualisiert', 'success');
        } else {
            $flash->addToast('Fehler beim Aktualisieren des Meetings', 'danger');
        }

        $redirect->back();
        break;

    case 'create_task':
        $organizationId = (int) ($session->get('DefaultOrganization') ?? 0);
        $meetingId = (int) ($_POST['meeting_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $deadline = trim((string) ($_POST['deadline'] ?? ''));
        $assigneeUserId = (int) ($_POST['assignee_user_id'] ?? 0);
        $meeting = $meetingRepository->findById($meetingId);

        if ($organizationId <= 0 || $meetingId <= 0 || $title === '' || $meeting === null || $meeting->organizationId !== $organizationId) {
            $flash->addToast('Meeting und Titel der Aufgabe sind erforderlich', 'danger');
        }

        if ($deadline !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline) || !checkdate((int) substr($deadline, 5, 2), (int) substr($deadline, 8, 2), (int) substr($deadline, 0, 4)))) {
            $flash->addToast('Ungültiges Fälligkeitsdatum', 'danger');
        }

        if ($assigneeUserId > 0 && $userRepository->findById($assigneeUserId) === null) {
            $flash->addToast('Verantwortliche Person nicht gefunden', 'danger');
        }

        $taskId = $taskRepository->create(new Task(
            meetingId: $meetingId,
            creatorUserId: (int) $session->get('id'),
            assigneeUserId: $assigneeUserId > 0 ? $assigneeUserId : null,
            title: $title,
            description: $description !== '' ? $description : null,
            deadline: $deadline !== '' ? $deadline : null,
        ));

        if ($taskId <= 0) {
            $flash->addToast('Fehler beim Erstellen der Aufgabe', 'danger');
        }

        $redirect->back();
        break;

    case 'update_task':
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $organizationId = (int) ($session->get('DefaultOrganization') ?? 0);
        $meetingId = (int) ($_POST['meeting_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $deadline = trim((string) ($_POST['deadline'] ?? ''));
        $assigneeUserId = (int) ($_POST['assignee_user_id'] ?? 0);
        $task = $taskRepository->findById($taskId);
        $meeting = $meetingRepository->findById($meetingId);
        $currentMeeting = $task !== null ? $meetingRepository->findById((int) $task->meetingId) : null;

        if ($organizationId <= 0 || $task === null || $meetingId <= 0 || $title === '' || $meeting === null || $currentMeeting === null || $meeting->organizationId !== $organizationId || $currentMeeting->organizationId !== $organizationId) {
            $flash->addToast('Aufgabe, Meeting und Titel sind erforderlich', 'danger');
        }

        if ($deadline !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline) || !checkdate((int) substr($deadline, 5, 2), (int) substr($deadline, 8, 2), (int) substr($deadline, 0, 4)))) {
            $flash->addToast('Ungültiges Fälligkeitsdatum', 'danger');
        }

        if ($assigneeUserId > 0 && $userRepository->findById($assigneeUserId) === null) {
            $flash->addToast('Verantwortliche Person nicht gefunden', 'danger');
        }

        $task->meetingId = $meetingId;
        $task->assigneeUserId = $assigneeUserId > 0 ? $assigneeUserId : null;
        $task->title = $title;
        $task->description = $description !== '' ? $description : null;
        $task->deadline = $deadline !== '' ? $deadline : null;
        $task->isDone = (string) ($_POST['is_done'] ?? '0') === '1';

        if (!$taskRepository->update($task)) {
            $flash->addToast('Fehler beim Bearbeiten der Aufgabe', 'danger');
        }

        $redirect->back();
        break;

    case 'update_task_status':
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        $taskId = (int) ($_POST['task_id'] ?? $_GET['task_id'] ?? 0);
        $isDone = (string) ($_POST['is_done'] ?? $_GET['is_done'] ?? '0') === '1';
        $organizationId = (int) ($session->get('DefaultOrganization') ?? 0);
        $task = $taskRepository->findById($taskId);
        $meeting = $task !== null ? $meetingRepository->findById((int) $task->meetingId) : null;

        if ($organizationId <= 0 || $task === null || $meeting === null || $meeting->organizationId !== $organizationId) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Aufgabe nicht gefunden oder nicht zugänglich',
            ]);
            exit;
        }

        $task->isDone = $isDone;

        if (!$taskRepository->update($task)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Fehler beim Aktualisieren des Aufgabenstatus',
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'taskId' => $taskId,
            'isDone' => $isDone,
        ]);
        exit;

    case 'delete_task':
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $organizationId = (int) ($session->get('DefaultOrganization') ?? 0);
        $task = $taskRepository->findById($taskId);
        $meeting = $task !== null ? $meetingRepository->findById((int) $task->meetingId) : null;

        if ($organizationId <= 0 || $task === null || $meeting === null || $meeting->organizationId !== $organizationId) {
            $flash->addToast('Aufgabe nicht gefunden oder nicht zugänglich', 'danger');
        }

        if (!$taskRepository->delete($taskId)) {
            $flash->addToast('Fehler beim Löschen der Aufgabe', 'danger');
        }

        $redirect->back();
        break;

    case 'create_person':
        $organizationId = (int) ($session->get('DefaultOrganization') ?? 0);

        if ($organizationId <= 0) {
            $flash->addToast('Keine gültige Standardorganisation gesetzt', 'danger');
        }

        $firstName = trim((string) ($_POST['firstName'] ?? ''));
        $lastName = trim((string) ($_POST['lastName'] ?? ''));
        $mailAddress = trim((string) ($_POST['mail'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $street = trim((string) ($_POST['street'] ?? ''));
        $zipCode = trim((string) ($_POST['zipCode'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $country = trim((string) ($_POST['country'] ?? ''));
        $countryId = ($country !== '' && ctype_digit($country)) ? (int) $country : null;
        $functionIdRaw = trim((string) ($_POST['function_id'] ?? ''));
        $functionId = ($functionIdRaw !== '' && ctype_digit($functionIdRaw)) ? (int) $functionIdRaw : null;
        $createUser = (string) ($_POST['create_user'] ?? '0') === '1';
        $password = trim((string) ($_POST['password'] ?? ''));

        if ($mailAddress !== '' && !filter_var($mailAddress, FILTER_VALIDATE_EMAIL)) {
            $flash->addToast('Ungültige E-Mail-Adresse', 'danger');
        }

        if ($createUser && $mailAddress === '') {
            $flash->addToast('Für ein Benutzerkonto ist eine E-Mail-Adresse erforderlich', 'danger');
        }

        $person = new Person(
            firstName: $firstName !== '' ? $firstName : null,
            lastName: $lastName !== '' ? $lastName : null,
            mail: $mailAddress !== '' ? $mailAddress : null,
            phone: $phone !== '' ? $phone : null,
            street: $street !== '' ? $street : null,
            zipCode: $zipCode !== '' ? $zipCode : null,
            city: $city !== '' ? $city : null,
            country: $countryId,
            isActive: 1,
            isDeleted: 0,
        );

        $personId = $personRepository->create($person);
        if (!$personId) {
            $flash->addToast('Fehler beim Erstellen der Person', 'danger');
        }

        $member = new OrganizationMember();
        $member->organization = $organizationId;
        $member->person = (int) $personId;
        $member->function = $functionId;
        $member->isActive = 1;
        $member->isDeleted = 0;

        $newMemberId = (int) $organizationMemberRepository->addMember($member);
        if ($newMemberId <= 0) {
            $flash->addToast('Fehler beim Verknüpfen der Person mit der Organisation', 'danger');
        }

        if ($createUser) {
            if ($password === '') {
                $password = bin2hex(random_bytes(12));
            }

            $user = new User();
            $user->person = (int) $personId;
            $user->firstName = $firstName;
            $user->lastName = $lastName;
            $user->mail = $mailAddress;
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $user->defaultOrganization = $organizationId;
            $user->isActive = 1;
            $user->isAdmin = 0;
            $user->isDeleted = 0;

            $newUserId = $userRepository->addUser($user);
            if (!$newUserId) {
                $flash->addToast('Fehler beim Erstellen des Benutzerkontos', 'danger');
            }

            $member = $organizationMemberRepository->findById($newMemberId);
            if ($member) {
                $member->user = (int) $newUserId;
                $organizationMemberRepository->updateMember($member);
            }

            $displayName = trim($firstName . ' ' . $lastName);
            $mail->sendWelcomeEmail($mailAddress, $displayName !== '' ? $displayName : $mailAddress, $mailAddress, $password, $organizationId);
        }

        $redirect->back();
        break;

    case 'update_person':
        try {
            $personId = (int) ($_POST['person_id'] ?? 0);
            $organizationId = (int) ($session->get('DefaultOrganization') ?? 0);

            if ($personId <= 0 || $organizationId <= 0) {
                $flash->addToast('Person oder Organisation ungültig', 'danger');
            }

            $member = $organizationMemberRepository->findByPersonAndOrganization($personId, $organizationId);
            if (!$member || $member->isDeleted) {
                $flash->addToast('Person gehört nicht zur aktuellen Organisation', 'danger');
            }

            $person = $personRepository->findById($personId);
            if (!$person || $person->isDeleted) {
                $flash->addToast('Person nicht gefunden', 'danger');
            }

            $firstName = trim((string) ($_POST['firstName'] ?? ''));
            $lastName = trim((string) ($_POST['lastName'] ?? ''));
            $mailAddress = trim((string) ($_POST['mail'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $street = trim((string) ($_POST['street'] ?? ''));
            $zipCode = trim((string) ($_POST['zipCode'] ?? ''));
            $city = trim((string) ($_POST['city'] ?? ''));
            $country = trim((string) ($_POST['country'] ?? ''));
            $countryId = ($country !== '' && ctype_digit($country)) ? (int) $country : null;
            $functionIdRaw = trim((string) ($_POST['function_id'] ?? ''));
            $functionId = ($functionIdRaw !== '' && ctype_digit($functionIdRaw)) ? (int) $functionIdRaw : null;

            if ($mailAddress !== '' && !filter_var($mailAddress, FILTER_VALIDATE_EMAIL)) {
                $flash->addToast('Ungültige E-Mail-Adresse', 'danger');
            }

            $person->firstName = $firstName !== '' ? $firstName : null;
            $person->lastName = $lastName !== '' ? $lastName : null;
            $person->mail = $mailAddress !== '' ? $mailAddress : null;
            $person->phone = $phone !== '' ? $phone : null;
            $person->street = $street !== '' ? $street : null;
            $person->zipCode = $zipCode !== '' ? $zipCode : null;
            $person->city = $city !== '' ? $city : null;
            $person->country = $countryId;

            // tblOrganizationMember hält eine denormalisierte Kopie der Personendaten, die die Listenansicht anzeigt
            $member->function = $functionId;
            $member->firstName = $person->firstName;
            $member->lastName = $person->lastName;
            $member->mail = $person->mail;
            $member->phone = $person->phone;
            $member->street = $person->street;
            $member->zipCode = $person->zipCode;
            $member->city = $person->city;
            $member->country = $person->country;

            $result = $personRepository->update($person);

            $logEntry = new Log(
                id: null,
                userId: (int) $_SESSION['id'],
                action: 'Person aktualisiert: ID ' . $personId . ', Ergebnis: ' . $result,
                severity: 'info',
                timestamp: date('Y-m-d H:i:s')
            );

            $logRepository->create($logEntry);

            if ($result) {
                $result = $organizationMemberRepository->updateMember($member);
                $flash->addToast('Person erfolgreich aktualisiert', 'success');
            }

            if (!$result) {
                $flash->addToast('Fehler beim Aktualisieren der Person', 'danger');
            }

            $redirect->back();
            break;
        } catch (Exception $e) {
            $flash->addToast('Fehler: ' . $e->getMessage(), 'danger');
            $logEntry = new Log(
                id: null,
                userId: (int) $_SESSION['id'],
                action: 'Fehler beim Aktualisieren der Person: ' . $e->getMessage(),
                severity: 'error',
                timestamp: date('Y-m-d H:i:s')
            );
            $logRepository->create($logEntry);
            $redirect->back();
        }

    case 'disable_person':
    case 'enable_person':
        $personId = (int) ($_GET['person_id'] ?? 0);
        $organizationId = (int) ($session->get('DefaultOrganization') ?? 0);

        if ($personId <= 0 || $organizationId <= 0) {
            $flash->addToast('Person oder Organisation ungültig', 'danger');
        }

        $person = $personRepository->findById($personId);
        if (!$person || $person->isDeleted) {
            $flash->addToast('Person nicht gefunden', 'danger');
        }

        $enable = $action === 'enable_person';
        $user = $userRepository->findByPersonId($personId);
        if (!$enable && $user && $user->isAdmin) {
            $flash->addToast('Admin-Benutzer kann nicht gesperrt werden', 'danger');
        }

        $person->isActive = $enable ? 1 : 0;
        $result = $personRepository->update($person);

        $member = $organizationMemberRepository->findByPersonAndOrganization($personId, $organizationId);
        if ($result && $member) {
            $member->isActive = $enable ? 1 : 0;
            $result = $organizationMemberRepository->updateMember($member);
        }

        if ($result && $user) {
            $user->isActive = $enable ? 1 : 0;
            $result = $userRepository->updateUser($user);
        }

        if (!$result) {
            $flash->addToast('Fehler beim Aktualisieren des Personenstatus', 'danger');
        }

        $redirect->back();
        break;

    case 'delete_person':
        $personId = (int) ($_GET['person_id'] ?? 0);
        $organizationId = (int) ($session->get('DefaultOrganization') ?? 0);

        if ($personId <= 0 || $organizationId <= 0) {
            $flash->addToast('Person oder Organisation ungültig', 'danger');
        }

        $person = $personRepository->findById($personId);
        if (!$person || $person->isDeleted) {
            $flash->addToast('Person nicht gefunden', 'danger');
        }

        $user = $userRepository->findByPersonId($personId);
        if ($user && $user->isAdmin) {
            $flash->addToast('Admin-Benutzer kann nicht gelöscht werden', 'danger');
        }

        $mailAddress = (string) ($person->mail ?? '');

        $person->isActive = 0;
        $person->isDeleted = 1;
        $person->firstName = 'Anonym';
        $person->lastName = 'Anonym';
        $person->mail = 'anonym_' . bin2hex(random_bytes(8)) . '@example.com';
        $person->phone = null;
        $person->street = null;
        $person->zipCode = null;
        $person->city = null;
        $person->country = null;

        $result = $personRepository->update($person);

        $member = $organizationMemberRepository->findByPersonAndOrganization($personId, $organizationId);
        if ($result && $member) {
            $member->isActive = 0;
            $member->isDeleted = 1;
            $result = $organizationMemberRepository->updateMember($member);
        }

        if ($result && $user) {
            $user->firstName = 'Anonym';
            $user->lastName = 'Anonym';
            $user->mail = $person->mail;
            $user->isActive = 0;
            $user->isDeleted = 1;
            $user->password = password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT);
            $result = $userRepository->updateUser($user);
        }

        if (!$result) {
            $flash->addToast('Fehler beim Löschen der Person', 'danger');
        }

        if ($mailAddress !== '') {
            $mail->sendDeletionEmail($mailAddress, $organizationId);
        }

        $redirect->back();
        break;

    case 'create_user_for_person':
        $personId = (int) ($_GET['person_id'] ?? 0);
        $organizationId = (int) ($session->get('DefaultOrganization') ?? 0);

        if ($personId <= 0 || $organizationId <= 0) {
            $flash->addToast('Person oder Organisation ungültig', 'danger');
        }

        $person = $personRepository->findById($personId);
        if (!$person || $person->isDeleted) {
            $flash->addToast('Person nicht gefunden', 'danger');
        }

        if (empty($person->mail) || !filter_var((string) $person->mail, FILTER_VALIDATE_EMAIL)) {
            $flash->addToast('Die Person benötigt eine gültige E-Mail-Adresse', 'danger');
        }

        $existingUser = $userRepository->findByPersonId($personId);
        if ($existingUser) {
            $flash->addToast('Für diese Person existiert bereits ein Benutzerkonto', 'danger');
        }

        $tempPassword = bin2hex(random_bytes(12));
        $user = new User();
        $user->person = $personId;
        $user->firstName = (string) ($person->firstName ?? '');
        $user->lastName = (string) ($person->lastName ?? '');
        $user->mail = (string) $person->mail;
        $user->password = password_hash($tempPassword, PASSWORD_DEFAULT);
        $user->defaultOrganization = $organizationId;
        $user->isActive = 1;
        $user->isAdmin = 0;
        $user->isDeleted = 0;

        $newUserId = $userRepository->addUser($user);
        if (!$newUserId) {
            $flash->addToast('Fehler beim Erstellen des Benutzerkontos', 'danger');
        }

        $member = $organizationMemberRepository->findByPersonAndOrganization($personId, $organizationId);
        if ($member) {
            $member->user = (int) $newUserId;
            $organizationMemberRepository->updateMember($member);
        }

        $displayName = trim((string) ($person->firstName ?? '') . ' ' . (string) ($person->lastName ?? ''));
        $mail->sendWelcomeEmail((string) $person->mail, $displayName !== '' ? $displayName : (string) $person->mail, (string) $person->mail, $tempPassword, $organizationId);

        $redirect->back();
        break;

    case 'reset_password_for_person':
        $personId = (int) ($_GET['person_id'] ?? 0);
        $organizationId = (int) ($session->get('DefaultOrganization') ?? 0);

        if ($personId <= 0 || $organizationId <= 0) {
            $flash->addToast('Person oder Organisation ungültig', 'danger');
        }

        $person = $personRepository->findById($personId);
        $user = $userRepository->findByPersonId($personId);

        if (!$person || !$user) {
            $flash->addToast('Person oder Benutzerkonto nicht gefunden', 'danger');
        }

        if (empty($person->mail) || !filter_var((string) $person->mail, FILTER_VALIDATE_EMAIL)) {
            $flash->addToast('Keine gültige E-Mail-Adresse für den Versand vorhanden', 'danger');
        }

        $tempPassword = bin2hex(random_bytes(12));
        $updated = $userRepository->updatePasswordHash((int) $user->id, password_hash($tempPassword, PASSWORD_DEFAULT));

        if (!$updated) {
            $flash->addToast('Fehler beim Zurücksetzen des Passworts', 'danger');
        }

        $displayName = trim((string) ($person->firstName ?? '') . ' ' . (string) ($person->lastName ?? ''));
        $mail->sendWelcomeEmail((string) $person->mail, $displayName !== '' ? $displayName : (string) $person->mail, (string) $person->mail, $tempPassword, $organizationId);

        $redirect->back();
        break;


    case 'send_welcome_email':
        $memberId = $_GET['member_id'] ?? null;

        if (!$memberId) {
            $flash->addToast('MitgliedsID fehlt', 'danger');
        }

        $member = $organizationMemberRepository->findById((int) $memberId);

        if (!$member) {
            $flash->addToast('Mitglied nicht gefunden', 'danger');
        }

        $user = $userRepository->findById($member->user);

        if (!$user) {
            $flash->addToast('Benutzerkonto nicht gefunden', 'danger');
        }

        // Generiere ein temporäres Passwort
        $tempPassword = bin2hex(random_bytes(12));
        $userRepository->updatePasswordHash($user->id, password_hash($tempPassword, PASSWORD_DEFAULT));

        // Sende die Willkommens-E-Mail mit dem temporären Passwort
        $recipientMail = (string) ($member->mail ?? $user->mail ?? '');
        $recipientName = (string) ($member->firstName ?? $user->firstName ?? '');

        if ($recipientMail === '' || !filter_var($recipientMail, FILTER_VALIDATE_EMAIL)) {
            $flash->addToast('Keine gültige E-Mail-Adresse für den Versand gefunden', 'danger');
        }

        $mail->sendWelcomeEmail($recipientMail, $recipientName, $recipientMail, $tempPassword, $member->organization);

        $redirect->back();
        break;

    case 'create_function':
        $organizationId = (int) ($session->get('DefaultOrganization') ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $isActive = isset($_POST['isActive']) ? (bool)$_POST['isActive'] : false;
        $maySign = isset($_POST['maySign']) ? 1 : 0;
        $mayManageOrganization = isset($_POST['mayManageOrganization']) ? 1 : 0;
        $mayManageMeetings = isset($_POST['mayManageMeetings']) ? 1 : 0;

        if ($organizationId <= 0) {
            $flash->addToast('Keine Organisation ausgewählt', 'danger');
            $redirect->back();
        }

        if ($name === '') {
            $flash->addToast('Bitte eine Bezeichnung für die Funktion angeben', 'danger');
            $redirect->back();
        }

        $function = new OrganizationFunction(
            id: null,
            organization: $organizationId,
            name: $name,
            description: $description !== '' ? $description : null,
            sortOrder: 0,
            isActive: $isActive,
            maySign: $maySign,
            mayManageOrganization: $mayManageOrganization,
            mayManageMeetings: $mayManageMeetings,
        );

        try {
            $newId = $organizationFunctionRepository->create($function);
        } catch (\Exception $e) {
            $newId = 0;
        }

        if ($newId <= 0) {
            $flash->addToast('Fehler beim Erstellen der Funktion', 'danger');
        } else {
            $flash->addToast('Funktion erfolgreich erstellt', 'success');
        }

        $redirect->back();
        break;

    case 'delete_function':
        $functionId = isset($_POST['functionId']) ? (int)$_POST['functionId'] : 0;

        if ($functionId <= 0) {
            $flash->addToast('Keine Funktion ausgewählt', 'danger');
            $redirect->back();
        }

        try {
            $deleted = $organizationFunctionRepository->delete($functionId);
        } catch (\Exception $e) {
            $deleted = false;
        }

        if (!$deleted) {
            $flash->addToast('Fehler beim Löschen der Funktion', 'danger');

            $logEntry = new Log(
                id: null,
                userId: (int) $_SESSION['id'],
                action: 'Funktion zu ' . $functionId . ' gelöscht',
                severity: 'info',
                timestamp: date('Y-m-d H:i:s')
            );
            $logRepository->create($logEntry);
        } else {
            $flash->addToast('Funktion erfolgreich gelöscht', 'success');

            $logEntry = new Log(
                id: null,
                userId: (int) $_SESSION['id'],
                action: 'Funktion zu ' . $functionId . ' gelöscht',
                severity: 'info',
                timestamp: date('Y-m-d H:i:s')
            );
            $logRepository->create($logEntry);
        }

        $redirect->back();
        break;

    case 'createFeedbackIssue':
        $payload = [
            'feedbackType' => trim((string)($_POST['feedbackType'] ?? '')),
            'feedbackTitle' => trim((string)($_POST['feedbackTitle'] ?? '')),
            'feedbackDescription' => trim((string)($_POST['feedbackDescription'] ?? '')),
            'feedbackName' => trim((string)($_POST['feedbackName'] ?? '')),
            'feedbackEmail' => trim((string)($_POST['feedbackEmail'] ?? '')),
            'instanceUrl' => (string)($_SERVER['HTTP_REFERER'] ?? ''),
            'appVersion' => $currentVersion,
            'userAgent' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'phpVersion' => phpversion(),
            'serverSoftware' => (string)($_SERVER['SERVER_SOFTWARE'] ?? ''),
        ];

        if (!function_exists('curl_init')) {
            $flash->addToast("Da ist was schief gegangen\n[Feedback/API] cURL nicht verfügbar", 'danger');
            $redirect->back();
        }

        $curl = curl_init('https://process.axellence.cloud/webhook/meetlog-feedback');

        if ($curl === false) {
            $flash->addToast("Da ist was schief gegangen\n[Feedback/API] cURL nicht verfügbar", 'danger');
            $redirect->back();
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        $statusCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($response === false || $statusCode !== 201) {
            $flash->addToast("Da ist was schief gegangen\n[Feedback/API] Fehler \n HTTP " . $statusCode . " \n cURL: " . ($curlError !== '' ? $curlError : 'kein cURL-Fehler') . " \n Response: " . (string)$response, 'danger');
            $redirect->back();
        }

        $decoded = json_decode((string)$response, true);

        if (!is_array($decoded) || !($decoded['ok'] ?? false)) {
            $flash->addToast("Da ist was schief gegangen\n" . ($decoded['message'] ?? ''), 'danger');
        } else {
            $flash->addToast("Vielen Dank für dein Feedback!\n" . ($decoded['message'] ?? ''), 'success');
        }

        $redirect->back();
        break;

    case 'clear_logs':
        $logRepository->clearLogs();
        $flash->addToast('Logs erfolgreich gelöscht', 'success');
        $redirect->back();
        break;

    default:
        $flash->addToast('Fehler', 'Unbekannte Aktion: ' . htmlspecialchars($action), 'danger');
        /*$flash->set('ErrorMsg', 'Wrong Action');
        $flash->set('ErrorColor', 'red');
        $flash->set('ErrorCode', '500');
        $redirect->to('error.php');*/
        $redirect->back();
        break;
};
