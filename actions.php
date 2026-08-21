<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/version.php';

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

use Src\User\User;
use Src\Organisation\OrganisationsMitglied;
use Src\Organisation\OrganisationMitgliedRepository;
use Src\Person\Person;
use Src\Besprechung\InvitationPdfBuilder;
use Src\Task\Task;

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
        }

        $redirect->back();
        break;

    case 'uploadProfilePhoto':

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $flash->addToast('Bitte Foto auswählen', 'danger');
        }

        $file = $_FILES['file'];
        $maxSize = 2 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            $flash->addToast('Datei zu groß. Maximal 2 MB', 'danger');
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
        }

        $relativePath = (string) '/upload/user/' . $newFilename;

        $userRepository->addProfilePhoto($session->get('id'), $relativePath);

        $redirect->back();

    case 'editMeeting':
        $meetingId = $_POST['editMeetingId'] ?? null;

        if (!$meetingId) {
            $flash->addToast('Meeting ID fehlt', 'danger');
        }

        $meeting = $besprechungRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
        }

        $meeting->titel = $_POST['editMeetingTitle'] ?? $meeting->titel;
        $meeting->ort = $_POST['editMeetingLocation'] ?? $meeting->ort;
        $meeting->datum = $_POST['editMeetingDate'] ?? $meeting->datum;
        $meeting->beginn = $_POST['editMeetingBegin'] ?? $meeting->beginn;
        $meeting->ende = $_POST['editMeetingEnde'] ?? $meeting->ende;

        $besprechungRepository->update($meeting);

        $redirect->back();
        break;

    case 'changeMeetingStatus':
        $meetingId = $_POST['changeMeetingStatusId'] ?? null;
        $status = $_POST['changeMeetingStatusStatus'] ?? null;

        if (!$meetingId || !$status) {
            $flash->addToast('Meeting ID oder Status fehlt', 'danger');
        }

        $meeting = $besprechungRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
        }

        if (!in_array($status, ['geplant', 'live', 'beendet'])) {
            $flash->addToast('Ungültiger Status', 'danger');
        }

        $meeting->status = $status;
        $besprechungRepository->update($meeting);

        $redirect->back();
        break;

    case 'publishMeeting':
        $meetingId = $_POST['publishMeetingId'] ?? null;

        if (!$meetingId) {
            $flash->addToast('Meeting ID fehlt', 'danger');
        }

        $meeting = $besprechungRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
        }

        if ($meeting->status !== 'beendet') {
            $flash->addToast('Nur Meetings mit Status "beendet" können veröffentlicht werden', 'danger');
        }

        $meeting->istVeröffentlicht = true;
        $meeting->public_code = bin2hex(random_bytes(8));
        $besprechungRepository->update($meeting);

        $redirect->back();
        break;

    case 'unpublishMeeting':
        $meetingId = $_POST['unpublishMeetingId'] ?? null;

        if (!$meetingId) {
            $flash->addToast('Meeting ID fehlt', 'danger');
        }

        $meeting = $besprechungRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
        }

        $meeting->istVeröffentlicht = false;
        $meeting->public_code = null;
        $besprechungRepository->update($meeting);

        $redirect->back();
        break;

    case 'deleteMeeting':
        $meetingId = $_POST['deleteMeetingId'] ?? null;

        if (!$meetingId) {
            $flash->addToast('Meeting ID fehlt', 'danger');
        }

        $meeting = $besprechungRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
        }

        $meeting->istAktiviert = false;

        $besprechungRepository->update($meeting);

        $redirect->back();
        break;

    case 'addMeetingPoint':
        $meetingId = $_POST['newMeetingPointId'] ?? null;

        if (!$meetingId) {
            $flash->addToast('Meeting ID fehlt', 'danger');
        }

        $meeting = $besprechungRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
        }

        $parentId = (int)($_POST['newMeetingPointParent'] ?? 0);
        $parentPosition = 0;

        if ($parentId > 0) {
            $parentMeetingPoint = $besprechungspunktRepository->findById($parentId);

            if ($parentMeetingPoint && $parentMeetingPoint->besprechungId === (int)$meetingId) {
                $parentPosition = $parentMeetingPoint->position;
            }
        }

        $newMeetingPoint = new \Src\Besprechung\Besprechungspunkt();
        $newMeetingPoint->besprechungId = (int)$meetingId;
        $newMeetingPoint->position = $besprechungspunktRepository->findNextPosition((int)$meetingId, $parentPosition);
        $newMeetingPoint->titel = $_POST['newMeetingPointTitle'] ?? '';
        $newMeetingPoint->beschreibung = $_POST['newMeetingPointDescription'] ?? '';
        $newMeetingPoint->typ = $_POST['newMeetingPointType'] ?? '';
        $newMeetingPoint->elternPosition = $parentPosition;
        $newMeetingPoint->ergebnis = $_POST['newMeetingPointResult'] ?? '';

        $result = $besprechungspunktRepository->insert($newMeetingPoint);

        if (!$result) {
            $flash->addToast('Fehler beim Hinzufügen des Besprechungspunkts', 'danger');
        }

        $redirect->back();
        break;

    case 'editMeetingPoint':
        $meetingPointId = $_POST['editMeetingPointId'] ?? null;

        if (!$meetingPointId) {
            $flash->addToast('Meeting Point ID fehlt', 'danger');
        }

        $meetingPoint = $besprechungspunktRepository->findById((int)$meetingPointId);

        if (!$meetingPoint) {
            $flash->addToast('Meeting Point nicht gefunden', 'danger');
        }

        $meetingPoint->beschreibung = $_POST['editMeetingPointDescription'] ?? $meetingPoint->beschreibung;
        $meetingPoint->typ = $_POST['editMeetingPointType'] ?? $meetingPoint->typ;
        $meetingPoint->elternPosition = (int)($_POST['editMeetingPointParent'] ?? $meetingPoint->elternPosition);
        $meetingPoint->ergebnis = $_POST['editMeetingPointResult'] ?? $meetingPoint->ergebnis;

        $meetingPoint->ergebnis_zustimmung = (int)($_POST['editMeetingPointVotingYes'] ?? $meetingPoint->ergebnis_zustimmung);
        $meetingPoint->ergebnis_ablehnung = (int)($_POST['editMeetingPointVotingNo'] ?? $meetingPoint->ergebnis_ablehnung);
        $meetingPoint->ergebnis_enthaltung = (int)($_POST['editMeetingPointVotingAbstain'] ?? $meetingPoint->ergebnis_enthaltung);

        $result = $besprechungspunktRepository->update($meetingPoint);

        $redirect->back();
        break;

    case 'meetingChairman':
        $meetingId = $_GET['meetingId'] ?? null;
        $participantId = $_GET['participantId'] ?? null;

        if (!$meetingId || !$participantId) {
            $flash->addToast('Meeting ID oder Teilnehmer ID fehlt', 'danger');
        }

        $meeting = $besprechungRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
        }

        $participant = $besprechungsteilnehmerRepository->findById((int)$participantId);

        if (!$participant) {
            $flash->addToast('Teilnehmer nicht gefunden', 'danger');
        }

        // Setze den aktuellen Vorsitzenden auf false
        $currentChairman = $besprechungsteilnehmerRepository->findChairmanByMeetingId((int)$meetingId);
        if ($currentChairman) {
            $currentChairman->istSitzungsleitung = false;
            $besprechungsteilnehmerRepository->updateTeilnehmer((int) $meetingId, $currentChairman);
        }

        // Setze den neuen Vorsitzenden auf true
        $participant->istSitzungsleitung = true;
        $besprechungsteilnehmerRepository->updateTeilnehmer((int) $meetingId, $participant);

        $redirect->back();
        break;

    case 'meetingSecretary':
        $meetingId = $_GET['meetingId'] ?? null;
        $participantId = $_GET['participantId'] ?? null;

        if (!$meetingId || !$participantId) {
            $flash->addToast('Meeting ID oder Teilnehmer ID fehlt', 'danger');
        }

        $meeting = $besprechungRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
        }

        $participant = $besprechungsteilnehmerRepository->findById((int)$participantId);

        if (!$participant) {
            $flash->addToast('Teilnehmer nicht gefunden', 'danger');
        }

        // Setze den aktuellen Protokollführer auf false
        $currentSecretary = $besprechungsteilnehmerRepository->findSecretaryByMeetingId((int)$meetingId);
        if ($currentSecretary) {
            $currentSecretary->istProtokollfuehrer = false;
            $besprechungsteilnehmerRepository->updateTeilnehmer((int) $meetingId, $currentSecretary);
        }

        // Setze den neuen Protokollführer auf true
        $participant->istProtokollfuehrer = true;
        $besprechungsteilnehmerRepository->updateTeilnehmer((int) $meetingId, $participant);

        $redirect->back();
        break;

    case 'meetingSign':
        $meetingId = $_GET['meetingId'] ?? null;
        $participantId = $_GET['participantId'] ?? null;

        if (!$meetingId || !$participantId) {
            $flash->addToast('Meeting ID oder Teilnehmer ID fehlt', 'danger');
        }

        $meeting = $besprechungRepository->findById((int)$meetingId);

        if (!$meeting) {
            $flash->addToast('Meeting nicht gefunden', 'danger');
        }

        $participant = $besprechungsteilnehmerRepository->findById((int)$participantId);

        if (!$participant) {
            $flash->addToast('Teilnehmer nicht gefunden', 'danger');
        }

        // Toggle the signing authorization
        $participant->darfUnterschreiben = !$participant->darfUnterschreiben;
        $besprechungsteilnehmerRepository->updateTeilnehmer((int) $meetingId, $participant);

        $redirect->back();
        break;

    case 'sendInvitations':
        $meetingId = $_POST['sendInvitationMeetingId'] ?? null;
        $participantIds = $_POST['sendInvitationParticipantIds'] ?? [];

        if (!$meetingId) {
            $flash->addToast('Meeting ID fehlt', 'danger');
        }

        $meeting = $besprechungRepository->findById((int)$meetingId);

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
            $participant = $besprechungsteilnehmerRepository->findById((int)$participantId);

            if (!$participant || $participant->besprechung !== $meeting->id) {
                $failedCount++;
                continue;
            }

            $pdfContent = InvitationPdfBuilder::render($app, $meeting, $participant);
            $attachmentName = 'Einladung_' . str_replace('.', '', str_replace(' ', '_', $meeting->titel)) . '.pdf';

            $recipientName = trim((string)$participant->vorname . ' ' . (string)$participant->nachname);
            $subject = $translator->t('meetings.participants.invitationMail.subject') . ': ' . $meeting->titel;

            $body = '<p>' . $translator->t('meetings.participants.invitationMail.greeting') . '</p>'
                . '<p>' . sprintf($translator->t('meetings.participants.invitationMail.body'), htmlspecialchars($meeting->titel, ENT_QUOTES, 'UTF-8')) . '</p>'
                . '<p>' . $translator->t('meetings.participants.invitationMail.closing') . '</p>'
                . $mail->imprint($meeting->organisationId);

            if (empty($participant->mail) || $participant->mail === null) {
                // Wenn die E-Mail-Adresse leer ist, wird die Einladung an den ausführenden Benutzer gesendet, damit er die Einladung postalisch verschicken kann
                $currentUser = $userRepository->findById((int)$session->get('id'));
                $fallbackMail = trim((string)($currentUser->mail ?? ''));

                if (!filter_var($fallbackMail, FILTER_VALIDATE_EMAIL)) {
                    $failedCount++;
                    $missingFallbackRecipient = true;
                    continue;
                }

                $sent = $mail->send($fallbackMail, 'Einladung postalisch verschicken: ' . $meeting->titel, $body, [
                    ['name' => $attachmentName, 'content' => $pdfContent],
                ]);
            } else {
                $sent = $mail->send($participant->mail, $subject, $body, [
                    ['name' => $attachmentName, 'content' => $pdfContent],
                ]);
            }

            if ($sent) {
                $participant->istEingeladen = true;
                $besprechungsteilnehmerRepository->updateTeilnehmer((int)$meeting->id, $participant);
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

    case 'updateOrganisation':
        $orgId = $_POST['organisationId'] ?? null;

        if (!$orgId) {
            $flash->addToast('OrganisationsID fehlt', 'danger');
        }

        $org = $organisationRepository->findById((int) $orgId);

        if (!$org) {
            $flash->addToast('Organisation nicht gefunden', 'danger');
        }

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo'];
            $maxSize = 2 * 1024 * 1024;

            if ($file['size'] > $maxSize) {
                $flash->addToast('Datei zu groß. Maximal 2 MB', 'danger');
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
            }

            $relativePath = (string) '/upload/organization/' . $newFilename;

            $org->logo = $relativePath;
        }

        $org->name1 = $_POST['name1'] ?? $org->name1;
        $org->name2 = $_POST['name2'] ?? $org->name2;
        $org->homepage = $_POST['homepage'] ?? $org->homepage;
        $org->email = $_POST['email'] ?? $org->email;
        $org->telefon = $_POST['telefon'] ?? $org->telefon;
        $org->telefax = $_POST['telefax'] ?? $org->telefax;
        $org->strasse = $_POST['strasse'] ?? $org->strasse;
        $org->plz = $_POST['plz'] ?? $org->plz;
        $org->ort = $_POST['ort'] ?? $org->ort;
        $org->land = $_POST['land'] ?? $org->land;
        $org->registergericht = $_POST['registergericht'] ?? $org->registergericht;
        $org->registereintrag = $_POST['registereintrag'] ?? $org->registereintrag;

        $organisationRepository->update($org);

        $redirect->back();

        break;

    case 'saveConfig':
        $data = $_POST ?? [];

        if (empty($data)) {
            $flash->addToast('Keine Daten zum speichern gefunden', 'danger');
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
        $orgId = $_POST['organisation_id'] ?? null;
        $vorname = trim($_POST['vorname'] ?? '');
        $nachname = trim($_POST['nachname'] ?? '');
        $strasse = trim($_POST['strasse'] ?? '');
        $plz = trim($_POST['plz'] ?? '');
        $ort = trim($_POST['ort'] ?? '');
        $land = trim($_POST['land'] ?? '');
        $telefon = trim($_POST['telefon'] ?? '');
        $email = trim($_POST['mail'] ?? '');
        $pass = trim($_POST['password'] ?? '');
        $funktionId = $_POST['funktion_id'] ?? null;
        $landId = ($land !== '' && ctype_digit($land)) ? (int) $land : null;

        # Wenn eine E-Mail-Adresse angegeben wurde, dann soll auch ein Benutzerkonto angelegt werden

        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $flash->addToast('Ungültige E-Mail-Adresse', 'danger');
            }
        }

        $person = new Person(
            vorname: $vorname !== '' ? $vorname : null,
            nachname: $nachname !== '' ? $nachname : null,
            mail: $email !== '' ? $email : null,
            telefon: $telefon !== '' ? $telefon : null,
            strasse: $strasse !== '' ? $strasse : null,
            plz: $plz !== '' ? $plz : null,
            ort: $ort !== '' ? $ort : null,
            land: $landId,
            istAktiv: 1,
            istGeloescht: 0,
        );

        $personId = $personRepository->create($person);

        if (!$personId) {
            $flash->addToast('Fehler beim Erstellen der Personendaten', 'danger');
        }

        if (!empty($email)) {
            if (empty($pass)) {
                $pass = bin2hex(random_bytes(12));
            }

            $user = new User();
            $user->person = (int) $personId;
            $user->vorname = $vorname;
            $user->nachname = $nachname;
            $user->mail = $email;
            $user->password = password_hash($pass, PASSWORD_DEFAULT);
            $user->standardOrganisation = $session->get('StandardOrganisation');
            $user->istAktiv = 1;

            $newUserId = $userRepository->addUser($user);

            if (!$newUserId) {
                $flash->addToast('Fehler beim Erstellen des Benutzerkontos', 'danger');
            }
        }

        $mitglied = new OrganisationsMitglied();
        $mitglied->organisation = (int) $orgId;
        $mitglied->person = (int) $personId;
        $mitglied->user = isset($newUserId) ? (int) $newUserId : null;
        $mitglied->funktion = isset($funktionId) && $funktionId !== '' ? (int) $funktionId : null;
        $mitglied->vorname = $vorname;
        $mitglied->nachname = $nachname;
        $mitglied->strasse = $strasse;
        $mitglied->plz = $plz;
        $mitglied->ort = $ort;
        $mitglied->land = $landId;
        $mitglied->telefon = $telefon;
        $mitglied->mail = $email;
        $mitglied->istAktiv = 1;
        $mitglied->istGeloescht = 0;

        $result = (int) $organisationsMitgliedRepository->addMember($mitglied);

        if ($result) {
            if (isset($email) && isset($newUserId)) {
                $mail->sendWelcomeEmail($email, $vorname, $email, $pass);
            }
        } else {
            $flash->addToast('Fehler beim Erstellen des Mitglieds', 'danger');
        }

        $redirect->back();
        break;

    case 'enable_member':
    case 'disable_member':

        $memberId = $_GET['member_id'] ?? null;

        if (!$memberId) {
            $flash->addToast('MitgliedsID fehlt', 'danger');
        }

        $mitglied = $organisationsMitgliedRepository->findById((int) $memberId);

        if (!$mitglied) {
            $flash->addToast('Mitglied nicht gefunden', 'danger');
        }

        $user = $userRepository->findById($mitglied->user);

        if ($user && $user->istAdmin && $action === 'disable_member') {
            $flash->addToast('Admin-Mitglied kann nicht deaktiviert werden', 'danger');
        }

        $mitglied->istAktiv = ($action === 'enable_member');

        $result = $organisationsMitgliedRepository->update($mitglied);

        if (!$result) {
            $flash->addToast('Fehler beim Aktualisieren des Mitgliedsstatus', 'danger');
        }

        $redirect->back();
        break;

    case 'update_member':
        $memberId = $_POST['member_id'] ?? null;

        if (!$memberId) {
            $flash->addToast('MitgliedsID fehlt', 'danger');
        }

        $mitglied = $organisationsMitgliedRepository->findById((int) $memberId);

        if (!$mitglied) {
            $flash->addToast('Mitglied nicht gefunden', 'danger');
        }

        $mitglied->vorname = trim((string) ($_POST['vorname'] ?? (string) $mitglied->vorname));
        $mitglied->nachname = trim((string) ($_POST['nachname'] ?? (string) $mitglied->nachname));
        $mitglied->strasse = trim((string) ($_POST['strasse'] ?? (string) $mitglied->strasse));
        $mitglied->plz = trim((string) ($_POST['plz'] ?? (string) $mitglied->plz));
        $mitglied->ort = trim((string) ($_POST['ort'] ?? (string) $mitglied->ort));
        $postedLand = trim((string) ($_POST['land'] ?? ''));
        $mitglied->land = ($postedLand !== '' && ctype_digit($postedLand)) ? (int) $postedLand : $mitglied->land;
        $mitglied->telefon = trim((string) ($_POST['telefon'] ?? (string) $mitglied->telefon));
        $mitglied->mail = trim((string) ($_POST['mail'] ?? (string) $mitglied->mail));

        $result = (int) $organisationsMitgliedRepository->updateMember($mitglied);

        if (!$result) {
            $flash->addToast('Fehler beim Aktualisieren des Mitglieds', 'danger');
        }

        if ($mitglied->user) {
            // Wenn das Mitglied ein Benutzerkonto hat, dann aktualisiere auch das Benutzerkonto
            $user = $userRepository->findById((int) $mitglied->user);

            if ($user) {
                $user->person = $mitglied->person;
                $user->vorname = (string) ($mitglied->vorname ?? $user->vorname);
                $user->nachname = (string) ($mitglied->nachname ?? $user->nachname);
                $newMail = trim((string) ($_POST['mail'] ?? ''));

                if ($newMail !== '' && filter_var($newMail, FILTER_VALIDATE_EMAIL)) {
                    $user->mail = $newMail;
                }

                $userRepository->updateUser($user);
            }
        }

        $redirect->back();
        break;

    case 'delete_member':
        $memberId = $_GET['member_id'] ?? null;

        if (!$memberId) {
            $flash->addToast('MitgliedsID fehlt', 'danger');
        }

        $mitglied = $organisationsMitgliedRepository->findById((int) $memberId);

        if (!$mitglied) {
            $flash->addToast('Mitglied nicht gefunden', 'danger');
        }

        $user = $userRepository->findById($mitglied->user);

        if ($user && $user->istAdmin) {
            $flash->addToast('Admin-Mitglied kann nicht gelöscht werden', 'danger');
        }

        $mitglied->istAktiv = 0;
        $mitglied->istGeloescht = 1;

        $mailaddress = '';
        $result = $organisationsMitgliedRepository->updateMember($mitglied);

        if ($result && $user) {
            $user->person = $mitglied->person;
            $user->istAktiv = 0;
            $user->istGeloescht = 1;

            $mailaddress = (string) $user->mail; // Für Lösch-Benachrichtigung sichern

            // Person/User anonymisieren
            $user->vorname = 'Anonym';
            $user->nachname = 'Anonym';
            $user->mail = 'anonym_' . bin2hex(random_bytes(8)) . '@example.com';
            $user->password = password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT);

            $result = $userRepository->updateUser($user);
        }

        if (!$result) {
            $flash->addToast('Fehler beim Aktualisieren des Mitgliedsstatus', 'danger');
        }

        if ($mailaddress !== '') {
            $mail->sendDeletionEmail($mailaddress, $mitglied->organisation);
        }

        $redirect->back();
        break;

    case 'create_task':
        $organisationId = (int) ($session->get('StandardOrganisation') ?? 0);
        $besprechungId = (int) ($_POST['besprechung_id'] ?? 0);
        $titel = trim((string) ($_POST['titel'] ?? ''));
        $beschreibung = trim((string) ($_POST['beschreibung'] ?? ''));
        $deadline = trim((string) ($_POST['deadline'] ?? ''));
        $verantwortlicherUserId = (int) ($_POST['verantwortlicher_user_id'] ?? 0);
        $meeting = $besprechungRepository->findById($besprechungId);

        if ($organisationId <= 0 || $besprechungId <= 0 || $titel === '' || $meeting === null || $meeting->organisationId !== $organisationId) {
            $flash->addToast('Meeting und Titel der Aufgabe sind erforderlich', 'danger');
        }

        if ($deadline !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline) || !checkdate((int) substr($deadline, 5, 2), (int) substr($deadline, 8, 2), (int) substr($deadline, 0, 4)))) {
            $flash->addToast('Ungültiges Fälligkeitsdatum', 'danger');
        }

        if ($verantwortlicherUserId > 0 && $userRepository->findById($verantwortlicherUserId) === null) {
            $flash->addToast('Verantwortliche Person nicht gefunden', 'danger');
        }

        $taskId = $taskRepository->create(new Task(
            besprechungId: $besprechungId,
            erstellerUserId: (int) $session->get('id'),
            verantwortlicherUserId: $verantwortlicherUserId > 0 ? $verantwortlicherUserId : null,
            titel: $titel,
            beschreibung: $beschreibung !== '' ? $beschreibung : null,
            deadline: $deadline !== '' ? $deadline : null,
        ));

        if ($taskId <= 0) {
            $flash->addToast('Fehler beim Erstellen der Aufgabe', 'danger');
        }

        $redirect->back();
        break;

    case 'update_task':
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $organisationId = (int) ($session->get('StandardOrganisation') ?? 0);
        $besprechungId = (int) ($_POST['besprechung_id'] ?? 0);
        $titel = trim((string) ($_POST['titel'] ?? ''));
        $beschreibung = trim((string) ($_POST['beschreibung'] ?? ''));
        $deadline = trim((string) ($_POST['deadline'] ?? ''));
        $verantwortlicherUserId = (int) ($_POST['verantwortlicher_user_id'] ?? 0);
        $task = $taskRepository->findById($taskId);
        $meeting = $besprechungRepository->findById($besprechungId);
        $currentMeeting = $task !== null ? $besprechungRepository->findById((int) $task->besprechungId) : null;

        if ($organisationId <= 0 || $task === null || $besprechungId <= 0 || $titel === '' || $meeting === null || $currentMeeting === null || $meeting->organisationId !== $organisationId || $currentMeeting->organisationId !== $organisationId) {
            $flash->addToast('Aufgabe, Meeting und Titel sind erforderlich', 'danger');
        }

        if ($deadline !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline) || !checkdate((int) substr($deadline, 5, 2), (int) substr($deadline, 8, 2), (int) substr($deadline, 0, 4)))) {
            $flash->addToast('Ungültiges Fälligkeitsdatum', 'danger');
        }

        if ($verantwortlicherUserId > 0 && $userRepository->findById($verantwortlicherUserId) === null) {
            $flash->addToast('Verantwortliche Person nicht gefunden', 'danger');
        }

        $task->besprechungId = $besprechungId;
        $task->verantwortlicherUserId = $verantwortlicherUserId > 0 ? $verantwortlicherUserId : null;
        $task->titel = $titel;
        $task->beschreibung = $beschreibung !== '' ? $beschreibung : null;
        $task->deadline = $deadline !== '' ? $deadline : null;
        $task->istErledigt = (string) ($_POST['ist_erledigt'] ?? '0') === '1';

        if (!$taskRepository->update($task)) {
            $flash->addToast('Fehler beim Bearbeiten der Aufgabe', 'danger');
        }

        $redirect->back();
        break;

    case 'update_task_status':
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        $taskId = (int) ($_POST['task_id'] ?? $_GET['task_id'] ?? 0);
        $istErledigt = (string) ($_POST['ist_erledigt'] ?? $_GET['ist_erledigt'] ?? '0') === '1';
        $organisationId = (int) ($session->get('StandardOrganisation') ?? 0);
        $task = $taskRepository->findById($taskId);
        $meeting = $task !== null ? $besprechungRepository->findById((int) $task->besprechungId) : null;

        if ($organisationId <= 0 || $task === null || $meeting === null || $meeting->organisationId !== $organisationId) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Aufgabe nicht gefunden oder nicht zugänglich',
            ]);
            exit;
        }

        $task->istErledigt = $istErledigt;

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
            'istErledigt' => $istErledigt,
        ]);
        exit;

    case 'delete_task':
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $organisationId = (int) ($session->get('StandardOrganisation') ?? 0);
        $task = $taskRepository->findById($taskId);
        $meeting = $task !== null ? $besprechungRepository->findById((int) $task->besprechungId) : null;

        if ($organisationId <= 0 || $task === null || $meeting === null || $meeting->organisationId !== $organisationId) {
            $flash->addToast('Aufgabe nicht gefunden oder nicht zugänglich', 'danger');
        }

        if (!$taskRepository->delete($taskId)) {
            $flash->addToast('Fehler beim Löschen der Aufgabe', 'danger');
        }

        $redirect->back();
        break;

    case 'create_person':
        $organisationId = (int) ($session->get('StandardOrganisation') ?? 0);

        if ($organisationId <= 0) {
            $flash->addToast('Keine gültige Standardorganisation gesetzt', 'danger');
        }

        $vorname = trim((string) ($_POST['vorname'] ?? ''));
        $nachname = trim((string) ($_POST['nachname'] ?? ''));
        $mailAddress = trim((string) ($_POST['mail'] ?? ''));
        $telefon = trim((string) ($_POST['telefon'] ?? ''));
        $strasse = trim((string) ($_POST['strasse'] ?? ''));
        $plz = trim((string) ($_POST['plz'] ?? ''));
        $ort = trim((string) ($_POST['ort'] ?? ''));
        $land = trim((string) ($_POST['land'] ?? ''));
        $landId = ($land !== '' && ctype_digit($land)) ? (int) $land : null;
        $funktionIdRaw = trim((string) ($_POST['funktion_id'] ?? ''));
        $funktionId = ($funktionIdRaw !== '' && ctype_digit($funktionIdRaw)) ? (int) $funktionIdRaw : null;
        $createUser = (string) ($_POST['create_user'] ?? '0') === '1';
        $password = trim((string) ($_POST['password'] ?? ''));

        if ($mailAddress !== '' && !filter_var($mailAddress, FILTER_VALIDATE_EMAIL)) {
            $flash->addToast('Ungültige E-Mail-Adresse', 'danger');
        }

        if ($createUser && $mailAddress === '') {
            $flash->addToast('Für ein Benutzerkonto ist eine E-Mail-Adresse erforderlich', 'danger');
        }

        $person = new Person(
            vorname: $vorname !== '' ? $vorname : null,
            nachname: $nachname !== '' ? $nachname : null,
            mail: $mailAddress !== '' ? $mailAddress : null,
            telefon: $telefon !== '' ? $telefon : null,
            strasse: $strasse !== '' ? $strasse : null,
            plz: $plz !== '' ? $plz : null,
            ort: $ort !== '' ? $ort : null,
            land: $landId,
            istAktiv: 1,
            istGeloescht: 0,
        );

        $personId = $personRepository->create($person);
        if (!$personId) {
            $flash->addToast('Fehler beim Erstellen der Person', 'danger');
        }

        $mitglied = new OrganisationsMitglied();
        $mitglied->organisation = $organisationId;
        $mitglied->person = (int) $personId;
        $mitglied->funktion = $funktionId;
        $mitglied->istAktiv = 1;
        $mitglied->istGeloescht = 0;

        $newMemberId = (int) $organisationsMitgliedRepository->addMember($mitglied);
        if ($newMemberId <= 0) {
            $flash->addToast('Fehler beim Verknüpfen der Person mit der Organisation', 'danger');
        }

        if ($createUser) {
            if ($password === '') {
                $password = bin2hex(random_bytes(12));
            }

            $user = new User();
            $user->person = (int) $personId;
            $user->vorname = $vorname;
            $user->nachname = $nachname;
            $user->mail = $mailAddress;
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $user->standardOrganisation = $organisationId;
            $user->istAktiv = 1;
            $user->istAdmin = 0;
            $user->istGeloescht = 0;

            $newUserId = $userRepository->addUser($user);
            if (!$newUserId) {
                $flash->addToast('Fehler beim Erstellen des Benutzerkontos', 'danger');
            }

            $mitglied = $organisationsMitgliedRepository->findById($newMemberId);
            if ($mitglied) {
                $mitglied->user = (int) $newUserId;
                $organisationsMitgliedRepository->updateMember($mitglied);
            }

            $displayName = trim($vorname . ' ' . $nachname);
            $mail->sendWelcomeEmail($mailAddress, $displayName !== '' ? $displayName : $mailAddress, $mailAddress, $password, $organisationId);
        }

        $redirect->back();
        break;

    case 'update_person':
        $personId = (int) ($_POST['person_id'] ?? 0);
        $organisationId = (int) ($session->get('StandardOrganisation') ?? 0);

        if ($personId <= 0 || $organisationId <= 0) {
            $flash->addToast('Person oder Organisation ungültig', 'danger');
        }

        $mitglied = $organisationsMitgliedRepository->findByPersonAndOrganisation($personId, $organisationId);
        if (!$mitglied || $mitglied->istGeloescht) {
            $flash->addToast('Person gehört nicht zur aktuellen Organisation', 'danger');
        }

        $person = $personRepository->findById($personId);
        if (!$person || $person->istGeloescht) {
            $flash->addToast('Person nicht gefunden', 'danger');
        }

        $vorname = trim((string) ($_POST['vorname'] ?? ''));
        $nachname = trim((string) ($_POST['nachname'] ?? ''));
        $mailAddress = trim((string) ($_POST['mail'] ?? ''));
        $telefon = trim((string) ($_POST['telefon'] ?? ''));
        $strasse = trim((string) ($_POST['strasse'] ?? ''));
        $plz = trim((string) ($_POST['plz'] ?? ''));
        $ort = trim((string) ($_POST['ort'] ?? ''));
        $land = trim((string) ($_POST['land'] ?? ''));
        $landId = ($land !== '' && ctype_digit($land)) ? (int) $land : null;
        $funktionIdRaw = trim((string) ($_POST['funktion_id'] ?? ''));
        $funktionId = ($funktionIdRaw !== '' && ctype_digit($funktionIdRaw)) ? (int) $funktionIdRaw : null;

        if ($mailAddress !== '' && !filter_var($mailAddress, FILTER_VALIDATE_EMAIL)) {
            $flash->addToast('Ungültige E-Mail-Adresse', 'danger');
        }

        $person->vorname = $vorname !== '' ? $vorname : null;
        $person->nachname = $nachname !== '' ? $nachname : null;
        $person->mail = $mailAddress !== '' ? $mailAddress : null;
        $person->telefon = $telefon !== '' ? $telefon : null;
        $person->strasse = $strasse !== '' ? $strasse : null;
        $person->plz = $plz !== '' ? $plz : null;
        $person->ort = $ort !== '' ? $ort : null;
        $person->land = $landId;
        $mitglied->funktion = $funktionId;

        $result = $personRepository->update($person);

        if ($result) {
            $result = $organisationsMitgliedRepository->updateMember($mitglied);
        }

        if (!$result) {
            $flash->addToast('Fehler beim Aktualisieren der Person', 'danger');
        }

        $redirect->back();
        break;

    case 'disable_person':
    case 'enable_person':
        $personId = (int) ($_GET['person_id'] ?? 0);
        $organisationId = (int) ($session->get('StandardOrganisation') ?? 0);

        if ($personId <= 0 || $organisationId <= 0) {
            $flash->addToast('Person oder Organisation ungültig', 'danger');
        }

        $person = $personRepository->findById($personId);
        if (!$person || $person->istGeloescht) {
            $flash->addToast('Person nicht gefunden', 'danger');
        }

        $enable = $action === 'enable_person';
        $user = $userRepository->findByPersonId($personId);
        if (!$enable && $user && $user->istAdmin) {
            $flash->addToast('Admin-Benutzer kann nicht gesperrt werden', 'danger');
        }

        $person->istAktiv = $enable ? 1 : 0;
        $result = $personRepository->update($person);

        $mitglied = $organisationsMitgliedRepository->findByPersonAndOrganisation($personId, $organisationId);
        if ($result && $mitglied) {
            $mitglied->istAktiv = $enable ? 1 : 0;
            $result = $organisationsMitgliedRepository->updateMember($mitglied);
        }

        if ($result && $user) {
            $user->istAktiv = $enable ? 1 : 0;
            $result = $userRepository->updateUser($user);
        }

        if (!$result) {
            $flash->addToast('Fehler beim Aktualisieren des Personenstatus', 'danger');
        }

        $redirect->back();
        break;

    case 'delete_person':
        $personId = (int) ($_GET['person_id'] ?? 0);
        $organisationId = (int) ($session->get('StandardOrganisation') ?? 0);

        if ($personId <= 0 || $organisationId <= 0) {
            $flash->addToast('Person oder Organisation ungültig', 'danger');
        }

        $person = $personRepository->findById($personId);
        if (!$person || $person->istGeloescht) {
            $flash->addToast('Person nicht gefunden', 'danger');
        }

        $user = $userRepository->findByPersonId($personId);
        if ($user && $user->istAdmin) {
            $flash->addToast('Admin-Benutzer kann nicht gelöscht werden', 'danger');
        }

        $mailAddress = (string) ($person->mail ?? '');

        $person->istAktiv = 0;
        $person->istGeloescht = 1;
        $person->vorname = 'Anonym';
        $person->nachname = 'Anonym';
        $person->mail = 'anonym_' . bin2hex(random_bytes(8)) . '@example.com';
        $person->telefon = null;
        $person->strasse = null;
        $person->plz = null;
        $person->ort = null;
        $person->land = null;

        $result = $personRepository->update($person);

        $mitglied = $organisationsMitgliedRepository->findByPersonAndOrganisation($personId, $organisationId);
        if ($result && $mitglied) {
            $mitglied->istAktiv = 0;
            $mitglied->istGeloescht = 1;
            $result = $organisationsMitgliedRepository->updateMember($mitglied);
        }

        if ($result && $user) {
            $user->vorname = 'Anonym';
            $user->nachname = 'Anonym';
            $user->mail = $person->mail;
            $user->istAktiv = 0;
            $user->istGeloescht = 1;
            $user->password = password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT);
            $result = $userRepository->updateUser($user);
        }

        if (!$result) {
            $flash->addToast('Fehler beim Löschen der Person', 'danger');
        }

        if ($mailAddress !== '') {
            $mail->sendDeletionEmail($mailAddress, $organisationId);
        }

        $redirect->back();
        break;

    case 'create_user_for_person':
        $personId = (int) ($_GET['person_id'] ?? 0);
        $organisationId = (int) ($session->get('StandardOrganisation') ?? 0);

        if ($personId <= 0 || $organisationId <= 0) {
            $flash->addToast('Person oder Organisation ungültig', 'danger');
        }

        $person = $personRepository->findById($personId);
        if (!$person || $person->istGeloescht) {
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
        $user->vorname = (string) ($person->vorname ?? '');
        $user->nachname = (string) ($person->nachname ?? '');
        $user->mail = (string) $person->mail;
        $user->password = password_hash($tempPassword, PASSWORD_DEFAULT);
        $user->standardOrganisation = $organisationId;
        $user->istAktiv = 1;
        $user->istAdmin = 0;
        $user->istGeloescht = 0;

        $newUserId = $userRepository->addUser($user);
        if (!$newUserId) {
            $flash->addToast('Fehler beim Erstellen des Benutzerkontos', 'danger');
        }

        $mitglied = $organisationsMitgliedRepository->findByPersonAndOrganisation($personId, $organisationId);
        if ($mitglied) {
            $mitglied->user = (int) $newUserId;
            $organisationsMitgliedRepository->updateMember($mitglied);
        }

        $displayName = trim((string) ($person->vorname ?? '') . ' ' . (string) ($person->nachname ?? ''));
        $mail->sendWelcomeEmail((string) $person->mail, $displayName !== '' ? $displayName : (string) $person->mail, (string) $person->mail, $tempPassword, $organisationId);

        $redirect->back();
        break;

    case 'reset_password_for_person':
        $personId = (int) ($_GET['person_id'] ?? 0);
        $organisationId = (int) ($session->get('StandardOrganisation') ?? 0);

        if ($personId <= 0 || $organisationId <= 0) {
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

        $displayName = trim((string) ($person->vorname ?? '') . ' ' . (string) ($person->nachname ?? ''));
        $mail->sendWelcomeEmail((string) $person->mail, $displayName !== '' ? $displayName : (string) $person->mail, (string) $person->mail, $tempPassword, $organisationId);

        $redirect->back();
        break;

    /*case 'create_user':
        $member_id = $_GET['member_id'] ?? null;

        if (!$member_id) {
            $flash->addToast('MitgliedsID fehlt', 'danger');
        }

        $mitglied = $organisationsMitgliedRepository->findById((int) $member_id);

        if (!$mitglied) {
            $flash->addToast('Mitglied nicht gefunden', 'danger');
        }

        if ($mitglied->user) {
            $flash->addToast('Mitglied hat bereits ein Benutzerkonto', 'danger');
        }

        $user = new User();
        $user->vorname = $mitglied->vorname;
        $user->nachname = $mitglied->nachname;
        $user->mail = $mitglied->mail;
        $tempPassword = bin2hex(random_bytes(12));
        $user->password = password_hash($tempPassword, PASSWORD_DEFAULT);
        $user->standardOrganisation = $mitglied->organisation;
        $user->istAktiv = 1;
        $result = $userRepository->addUser($user);

        if (!$result) {
            $flash->addToast('Fehler beim Erstellen des Benutzerkontos', 'danger');
        } else {
            // Sende die Willkommens-E-Mail mit dem temporären Passwort
            $mail->sendWelcomeEmail($mitglied->mail, $mitglied->vorname, $mitglied->mail, $tempPassword, $mitglied->organisation);
        }

        $mitglied->user = $result;
        $organisationsMitgliedRepository->update($mitglied);

        $redirect->back();
        break;*/

    case 'send_welcome_email':
        $memberId = $_GET['member_id'] ?? null;

        if (!$memberId) {
            $flash->addToast('MitgliedsID fehlt', 'danger');
        }

        $mitglied = $organisationsMitgliedRepository->findById((int) $memberId);

        if (!$mitglied) {
            $flash->addToast('Mitglied nicht gefunden', 'danger');
        }

        $user = $userRepository->findById($mitglied->user);

        if (!$user) {
            $flash->addToast('Benutzerkonto nicht gefunden', 'danger');
        }

        // Generiere ein temporäres Passwort
        $tempPassword = bin2hex(random_bytes(12));
        $userRepository->updatePasswordHash($user->id, password_hash($tempPassword, PASSWORD_DEFAULT));

        // Sende die Willkommens-E-Mail mit dem temporären Passwort
        $recipientMail = (string) ($mitglied->mail ?? $user->mail ?? '');
        $recipientName = (string) ($mitglied->vorname ?? $user->vorname ?? '');

        if ($recipientMail === '' || !filter_var($recipientMail, FILTER_VALIDATE_EMAIL)) {
            $flash->addToast('Keine gültige E-Mail-Adresse für den Versand gefunden', 'danger');
        }

        $mail->sendWelcomeEmail($recipientMail, $recipientName, $recipientMail, $tempPassword, $mitglied->organisation);

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
            $flash->addToast('Da ist was schief gegangen\n' . '[Feedback/API] cURL nicht verfügbar', 'danger');
            $redirect->back();
        }

        $curl = curl_init('https://process.axellence.cloud/webhook/meetlog-feedback');

        if ($curl === false) {
            $flash->addToast('Da ist was schief gegangen\n' . '[Feedback/API] cURL nicht verfügbar', 'danger');
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
        curl_close($curl);

        if ($response === false || $statusCode !== 201) {
            $flash->addToast('Da ist was schief gegangen\n' . '[Feedback/API] Fehler \n HTTP ' . $statusCode . ' \n cURL: ' . ($curlError !== '' ? $curlError : 'kein cURL-Fehler') . ' \n Response: ' . (string)$response, 'danger');
            $redirect->back();
        }

        $decoded = json_decode((string)$response, true);

        if (!is_array($decoded) || !($decoded['ok'] ?? false)) {
            $flash->addToast('Da ist was schief gegangen\n' . ($decoded['message'] ?? ''), 'danger');
        } else {
            $flash->addToast('Vielen Dank für dein Feedback!\n' . ($decoded['message'] ?? ''), 'success');
        }

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
