<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
install_start();

$db = $_SESSION['install']['database'] ?? [
    'host' => 'localhost',
    'port' => 3306,
    'name' => '',
    'user' => '',
    'pass' => '',
];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    install_check_csrf();
    $db = [
        'host' => trim((string)($_POST['host'] ?? '')),
        'port' => (int)($_POST['port'] ?? 0),
        'name' => trim((string)($_POST['name'] ?? '')),
        'user' => trim((string)($_POST['user'] ?? '')),
        'pass' => (string)($_POST['pass'] ?? ''),
    ];

    try {
        if ($db['host'] === '' || $db['name'] === '' || $db['user'] === '' || $db['port'] < 1 || $db['port'] > 65535) {
            throw new RuntimeException('Bitte füllen Sie alle Datenbankfelder korrekt aus.');
        }
        install_db($db);
        $_SESSION['install']['database'] = $db;
        if (($_POST['action'] ?? '') === 'continue') {
            install_redirect('step2.php');
        }
        $message = 'Die Verbindung zur Datenbank wurde erfolgreich getestet.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

install_page_start('Datenbank-Anbindung', 2);
if ($message !== '') echo '<div class="alert alert-success">' . install_escape($message) . '</div>';
if ($error !== '') install_error($error);
?>
<p class="text-secondary">Geben Sie die Zugangsdaten Ihrer MariaDB- oder MySQL-Datenbank ein. Die Verbindung kann vor dem Fortfahren getestet werden.</p>
<form method="post">
    <div class="row">
        <div class="col-md-8 mb-3"><label class="form-label" for="host">Server</label><input class="form-control" id="host" name="host" value="<?= install_escape($db['host']) ?>" required></div>
        <div class="col-md-4 mb-3"><label class="form-label" for="port">Port</label><input class="form-control" id="port" name="port" type="number" min="1" max="65535" value="<?= (int)$db['port'] ?>" required></div>
    </div>
    <div class="mb-3"><label class="form-label" for="name">Datenbankname</label><input class="form-control" id="name" name="name" value="<?= install_escape($db['name']) ?>" required></div>
    <div class="mb-3"><label class="form-label" for="user">Benutzername</label><input class="form-control" id="user" name="user" value="<?= install_escape($db['user']) ?>" required></div>
    <div class="mb-3"><label class="form-label" for="pass">Passwort</label><input class="form-control" id="pass" name="pass" type="password" value="<?= install_escape($db['pass']) ?>" autocomplete="current-password"></div>
    <input type="hidden" name="csrf" value="<?= install_escape($_SESSION['install_csrf']) ?>">
    <div class="d-flex justify-content-between mt-4"><a class="btn btn-link" href="index.php">Zurück</a>
        <div><button class="btn btn-outline-primary me-2" name="action" value="test">Verbindung testen</button><button class="btn btn-primary" name="action" value="continue">Weiter</button></div>
    </div>
</form>
<?php install_page_end(); ?>