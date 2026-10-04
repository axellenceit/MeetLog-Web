<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
install_start();

$data = $_SESSION['install'];
$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    install_check_csrf();
    try {
        if (empty($data['url']) || empty($data['database']) || empty($data['mail']) || empty($data['mail_tested'])) {
            throw new RuntimeException('Die Installationsdaten sind unvollständig.');
        }

        $pdo = install_db($data['database']);
        install_execute_sql($pdo, __DIR__ . '/../data/01_create.sql');
        install_execute_sql($pdo, __DIR__ . '/../data/02_insert.sql');

        if (($data['mode'] ?? '') === 'demo') {
            install_execute_sql($pdo, __DIR__ . '/../data/10_demo.sql');
        } else {
            $custom = $data['custom'] ?? [];
            $pdo->beginTransaction();
            $organization = $pdo->prepare('INSERT INTO tblOrganization (Logo, Name1, Homepage, EMail, Country) VALUES (?, ?, ?, ?, ?)');
            $organization->execute(['', $custom['organization'], $data['url'], $custom['email'], 'DE']);
            $organizationId = (int)$pdo->lastInsertId();

            $person = $pdo->prepare('INSERT INTO tblPerson (FirstName, LastName, Mail, Country) VALUES (?, ?, ?, ?)');
            $person->execute([$custom['first_name'], $custom['last_name'], $custom['email'], 1]);
            $personId = (int)$pdo->lastInsertId();

            $user = $pdo->prepare('INSERT INTO tblUser (Person, Password, DefaultOrganization, Lang, IsAdmin) VALUES (?, ?, ?, ?, 1)');
            $user->execute([$personId, password_hash($custom['password'], PASSWORD_DEFAULT), $organizationId, 'de']);
            $userId = (int)$pdo->lastInsertId();

            $function = $pdo->prepare('INSERT INTO tblOrganizationFunction (Organization, Name, MayManageOrganization, MayManageMeetings) VALUES (?, ?, 1, 1)');
            $function->execute([$organizationId, 'Administrator']);
            $functionId = (int)$pdo->lastInsertId();

            $member = $pdo->prepare('INSERT INTO tblOrganizationMember (Organization, Person, User, Function) VALUES (?, ?, ?, ?)');
            $member->execute([$organizationId, $personId, $userId, $functionId]);
            $pdo->commit();
        }

        $ini = "[general]\n" . install_ini_line('url', $data['url']) . install_ini_line('togglePublicMeeting', 0) . "\n";
        $ini .= "[database]\n" . install_ini_line('host', $data['database']['host']) . install_ini_line('port', $data['database']['port']) . install_ini_line('name', $data['database']['name']) . install_ini_line('user', $data['database']['user']) . install_ini_line('pass', $data['database']['pass']) . install_ini_line('charset', 'utf8mb4') . "\n";
        $ini .= "[mail]\n" . install_ini_line('server', $data['mail']['server']) . install_ini_line('port', $data['mail']['port']) . install_ini_line('usessl', $data['mail']['usessl']) . install_ini_line('user', $data['mail']['user']) . install_ini_line('pass', $data['mail']['pass']) . install_ini_line('from', $data['mail']['from']) . "\n";

        $configFile = __DIR__ . '/../static/meetlog.ini';
        if (file_put_contents($configFile, $ini, LOCK_EX) === false) {
            throw new RuntimeException('Die Datei static/meetlog.ini konnte nicht geschrieben werden.');
        }
        if (file_put_contents(__DIR__ . '/../install.done', '') === false) {
            throw new RuntimeException('Die Datei install.done konnte nicht geschrieben werden.');
        }
        $done = true;
        session_destroy();
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $exception->getMessage();
    }
}

if ($done): ?>
    <!DOCTYPE html>
    <html lang="de">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>MeetLog installiert</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    </head>

    <body>
        <div class="page page-center">
            <div class="container container-tight">
                <div class="card">
                    <div class="card-body text-center"><img src="../img/meetlog_256.png" width="180" alt="MeetLog">
                        <h1 class="mt-4">Installation abgeschlossen</h1>
                        <p class="text-secondary">MeetLog wurde eingerichtet. Die Installationsdatei und der Schutz gegen einen erneuten Setup-Aufruf wurden erfolgreich geschrieben.</p><?php if (($data['mode'] ?? '') === 'demo'): ?><p>Demo-Anmeldung: <strong>anna.admin@example.com</strong> / <strong>demo1234</strong></p><?php endif; ?><a class="btn btn-primary" href="../login.php">Zu MeetLog</a>
                    </div>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php exit;
endif;

install_page_start('Installation abschließen', 5);
if ($error !== '') install_error($error);
?>
<p class="text-secondary">Die Datenbank wird angelegt und mit den ausgewählten Daten befüllt. Danach werden `static/meetlog.ini` und die Installationsmarkierung geschrieben.</p>
<form method="post"><input type="hidden" name="csrf" value="<?= install_escape($_SESSION['install_csrf']) ?>">
    <div class="d-flex justify-content-between"><a class="btn btn-link" href="step4.php">Zurück</a><button class="btn btn-primary">Installation starten</button></div>
</form>
<?php install_page_end();

function install_ini_line(string $key, mixed $value): string
{
    if (is_bool($value)) {
        return $key . ' = ' . ($value ? 'true' : 'false') . "\n";
    }
    return $key . ' = "' . addcslashes((string)$value, "\\\"") . '"' . "\n";
}
