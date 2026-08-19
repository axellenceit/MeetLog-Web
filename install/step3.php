<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
install_start();
if (($_SESSION['install']['mode'] ?? '') === 'demo') {
    install_redirect('step4.php');
}

$data = $_SESSION['install']['custom'] ?? [];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    install_check_csrf();
    $data = [
        'organization' => trim((string)($_POST['organization'] ?? '')),
        'first_name' => trim((string)($_POST['first_name'] ?? '')),
        'last_name' => trim((string)($_POST['last_name'] ?? '')),
        'email' => trim((string)($_POST['email'] ?? '')),
        'password' => (string)($_POST['password'] ?? ''),
        'password_confirm' => (string)($_POST['password_confirm'] ?? ''),
    ];
    if ($data['organization'] === '' || $data['first_name'] === '' || $data['last_name'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Bitte füllen Sie Organisation, Namen und eine gültige E-Mail-Adresse aus.';
    } elseif (strlen($data['password']) < 10 || $data['password'] !== $data['password_confirm']) {
        $error = 'Das Passwort muss mindestens 10 Zeichen lang sein und beide Eingaben müssen übereinstimmen.';
    } else {
        $_SESSION['install']['custom'] = $data;
        install_redirect('step4.php');
    }
}

install_page_start('Organisation und Administrator', 4);
if ($error !== '') install_error($error);
?>
<p class="text-secondary">Diese Angaben werden für den ersten Benutzer mit Administrationsrechten verwendet.</p>
<form method="post">
    <div class="mb-3"><label class="form-label" for="organization">Name der Organisation</label><input class="form-control" id="organization" name="organization" value="<?= install_escape($data['organization'] ?? '') ?>" required></div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label" for="first_name">Vorname</label><input class="form-control" id="first_name" name="first_name" value="<?= install_escape($data['first_name'] ?? '') ?>" required></div>
        <div class="col-md-6 mb-3"><label class="form-label" for="last_name">Nachname</label><input class="form-control" id="last_name" name="last_name" value="<?= install_escape($data['last_name'] ?? '') ?>" required></div>
    </div>
    <div class="mb-3"><label class="form-label" for="email">E-Mail-Adresse</label><input class="form-control" id="email" name="email" type="email" value="<?= install_escape($data['email'] ?? '') ?>" required></div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label" for="password">Passwort</label><input class="form-control" id="password" name="password" type="password" minlength="10" autocomplete="new-password" required></div>
        <div class="col-md-6 mb-3"><label class="form-label" for="password_confirm">Passwort wiederholen</label><input class="form-control" id="password_confirm" name="password_confirm" type="password" minlength="10" autocomplete="new-password" required></div>
    </div>
    <input type="hidden" name="csrf" value="<?= install_escape($_SESSION['install_csrf']) ?>">
    <div class="d-flex justify-content-between"><a class="btn btn-link" href="step2.php">Zurück</a><button class="btn btn-primary">Weiter</button></div>
</form>
<?php install_page_end(); ?>