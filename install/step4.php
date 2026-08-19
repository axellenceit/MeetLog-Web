<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
install_start();
$mail = $_SESSION['install']['mail'] ?? ['server' => '', 'port' => 587, 'usessl' => false, 'user' => '', 'pass' => '', 'from' => ''];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    install_check_csrf();
    $mail = ['server' => trim((string)($_POST['server'] ?? '')), 'port' => (int)($_POST['port'] ?? 0), 'usessl' => isset($_POST['usessl']), 'user' => trim((string)($_POST['user'] ?? '')), 'pass' => (string)($_POST['pass'] ?? ''), 'from' => trim((string)($_POST['from'] ?? ''))];
    try {
        if ($mail['server'] === '' || $mail['port'] < 1 || $mail['port'] > 65535 || !filter_var($mail['from'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Bitte geben Sie Server, Port und eine gültige Absenderadresse ein.');
        }
        install_smtp_test($mail);
        $_SESSION['install']['mail'] = $mail;
        $_SESSION['install']['mail_tested'] = true;
        $message = 'Der SMTP-Server ist erreichbar.';
        if (($_POST['action'] ?? '') === 'continue') install_redirect('finish.php');
    } catch (Throwable $exception) {
        $_SESSION['install']['mail_tested'] = false;
        $error = $exception->getMessage();
    }
}

install_page_start('Mail-Server', 5);
if ($message !== '') echo '<div class="alert alert-success">' . install_escape($message) . '</div>';
if ($error !== '') install_error($error);
?>
<p class="text-secondary">Der Installer prüft die Erreichbarkeit des SMTP-Servers. Die Zugangsdaten werden nur in der Konfigurationsdatei gespeichert.</p>
<form method="post">
    <div class="row">
        <div class="col-md-8 mb-3"><label class="form-label" for="server">SMTP-Server</label><input class="form-control" id="server" name="server" value="<?= install_escape($mail['server']) ?>" required></div>
        <div class="col-md-4 mb-3"><label class="form-label" for="port">Port</label><input class="form-control" id="port" name="port" type="number" min="1" max="65535" value="<?= (int)$mail['port'] ?>" required></div>
    </div>
    <div class="mb-3"><label class="form-check"><input class="form-check-input" type="checkbox" name="usessl" <?= $mail['usessl'] ? 'checked' : '' ?>><span class="form-check-label">SSL-Verbindung verwenden</span></label></div>
    <div class="mb-3"><label class="form-label" for="user">Benutzername</label><input class="form-control" id="user" name="user" value="<?= install_escape($mail['user']) ?>" autocomplete="username"></div>
    <div class="mb-3"><label class="form-label" for="pass">Passwort</label><input class="form-control" id="pass" name="pass" type="password" value="<?= install_escape($mail['pass']) ?>" autocomplete="current-password"></div>
    <div class="mb-3"><label class="form-label" for="from">Absenderadresse</label><input class="form-control" id="from" name="from" type="email" value="<?= install_escape($mail['from']) ?>" required></div>
    <input type="hidden" name="csrf" value="<?= install_escape($_SESSION['install_csrf']) ?>">
    <div class="d-flex justify-content-between"><a class="btn btn-link" href="<?= ($_SESSION['install']['mode'] ?? '') === 'demo' ? 'step2.php' : 'step3.php' ?>">Zurück</a>
        <div><button class="btn btn-outline-primary me-2" name="action" value="test">Server testen</button><button class="btn btn-primary" name="action" value="continue">Weiter</button></div>
    </div>
</form>
<?php install_page_end(); ?>