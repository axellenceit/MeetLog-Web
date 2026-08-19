<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
install_start();
$mode = (string)($_SESSION['install']['mode'] ?? 'custom');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    install_check_csrf();
    $mode = (string)($_POST['mode'] ?? '');
    if (!in_array($mode, ['demo', 'custom'], true)) {
        $error = 'Bitte wählen Sie eine Option.';
    } else {
        $_SESSION['install']['mode'] = $mode;
        install_redirect('step3.php');
    }
}

install_page_start('Datenbestand auswählen', 3);
if ($error !== '') install_error($error);
?>
<p class="text-secondary">Wählen Sie, ob MeetLog mit Beispieldaten oder als leere Installation eingerichtet werden soll.</p>
<form method="post">
    <label class="form-selectgroup-item mb-3 d-block"><input type="radio" name="mode" value="demo" class="form-selectgroup-input" <?= $mode === 'demo' ? 'checked' : '' ?>><span class="form-selectgroup-label"><strong>Demo-Daten laden</strong><br><span class="text-secondary">Organisation, Benutzer, Meetings und Aufgaben werden angelegt. Das Demo-Administratorkonto verwendet das Passwort <code>demo1234</code>.</span></span></label>
    <label class="form-selectgroup-item mb-3 d-block"><input type="radio" name="mode" value="custom" class="form-selectgroup-input" <?= $mode === 'custom' ? 'checked' : '' ?>><span class="form-selectgroup-label"><strong>Leere Installation</strong><br><span class="text-secondary">Sie legen im nächsten Schritt Ihre Organisation und Ihr Administratorkonto an.</span></span></label>
    <input type="hidden" name="csrf" value="<?= install_escape($_SESSION['install_csrf']) ?>">
    <div class="d-flex justify-content-between mt-4"><a class="btn btn-link" href="step1.php">Zurück</a><button class="btn btn-primary">Weiter</button></div>
</form>
<?php install_page_end(); ?>