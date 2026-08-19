<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

install_start();

$errors = [];
$url = (string)($_SESSION['install']['url'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    install_check_csrf();
    $url = trim((string)($_POST['url'] ?? ''));

    if (!install_valid_url($url)) {
        $errors[] = 'Bitte geben Sie eine gültige URL mit Hostnamen ein.';
    } else {
        $_SESSION['install']['url'] = rtrim($url, '/') . '/';
        install_redirect('step1.php');
    }
}

install_page_start('MeetLog einrichten', 1);
if ($errors) install_error(implode(' ', $errors));
?>
<p class="text-secondary">In wenigen Schritten richten Sie MeetLog und die Verbindung zu Ihrer Datenbank ein.</p>
<form method="post" action="">
    <div class="mb-3">
        <label class="form-label" for="url">URL Ihrer MeetLog-Installation</label>
        <input id="url" name="url" type="url" class="form-control" autocomplete="url" placeholder="https://meetlog.example.com" value="<?= install_escape($url) ?>" required>
    </div>
    <input type="hidden" name="csrf" value="<?= install_escape($_SESSION['install_csrf']) ?>">
    <div class="d-flex justify-content-between mt-4">
        <a href="../" class="btn btn-link">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Weiter</button>
    </div>
</form>
<?php install_page_end(); ?>