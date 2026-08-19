<?php

declare(strict_types=1);

function install_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (is_file(__DIR__ . '/../install.done')) {
        http_response_code(403);
        exit('MeetLog ist bereits installiert.');
    }

    if (!isset($_SESSION['install_csrf'])) {
        $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
    }

    if (!isset($_SESSION['install']) || !is_array($_SESSION['install'])) {
        $_SESSION['install'] = [];
    }
}

function install_check_csrf(): void
{
    if (!hash_equals((string)($_SESSION['install_csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Ungültige Formularanfrage.');
    }
}

function install_escape(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function install_redirect(string $page): never
{
    header('Location: ' . $page);
    exit;
}

function install_valid_url(string $url): bool
{
    if ($url === '') {
        return false;
    }

    $candidate = preg_match('~^https?://~i', $url) ? $url : 'https://' . $url;
    return filter_var($candidate, FILTER_VALIDATE_URL) !== false
        && (string)parse_url($candidate, PHP_URL_HOST) !== '';
}

function install_page_start(string $title, int $step): void
{
    $percent = min(100, max(0, $step * 20));
?>
    <!DOCTYPE html>
    <html lang="de">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= install_escape($title) ?> - MeetLog</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    </head>

    <body>
        <div class="page page-center">
            <div class="container container-tight py-4">
                <div class="text-center mb-4"><a href="." aria-label="MeetLog"><img src="../img/meetlog_256.png" width="220" alt="MeetLog"></a></div>
                <div class="progress progress-sm mb-3">
                    <div class="progress-bar" style="width: <?= $percent ?>%" role="progressbar" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="card card-md">
                    <div class="card-body">
                        <div class="text-secondary mb-2">Schritt <?= $step ?> von 5</div>
                        <h1 class="mb-3"><?= install_escape($title) ?></h1>
                    <?php
                }

                function install_page_end(): void
                {
                    ?>
                    </div>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
                }

                function install_error(string $message): void
                {
                    echo '<div class="alert alert-danger" role="alert">' . install_escape($message) . '</div>';
                }

                function install_db(array $config, bool $withDatabase = true): PDO
                {
                    $database = $withDatabase ? ';dbname=' . rawurlencode((string)$config['name']) : '';
                    $dsn = 'mysql:host=' . $config['host'] . ';port=' . (int)$config['port'] . $database . ';charset=utf8mb4';
                    return new PDO($dsn, (string)$config['user'], (string)$config['pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_EMULATE_PREPARES => true,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                }

                function install_execute_sql(PDO $pdo, string $file): void
                {
                    $sql = file_get_contents($file);
                    if ($sql === false) {
                        throw new RuntimeException('SQL-Datei konnte nicht gelesen werden.');
                    }
                    $pdo->exec($sql);
                }

                function install_smtp_test(array $mail): void
                {
                    $scheme = (bool)$mail['usessl'] ? 'ssl://' : '';
                    $socket = @fsockopen($scheme . $mail['server'], (int)$mail['port'], $errorNumber, $errorMessage, 8);
                    if (!is_resource($socket)) {
                        throw new RuntimeException('SMTP-Verbindung fehlgeschlagen: ' . $errorMessage);
                    }
                    stream_set_timeout($socket, 8);
                    $greeting = fgets($socket, 512);
                    fwrite($socket, "EHLO meetlog\r\n");
                    $response = fgets($socket, 512);
                    fwrite($socket, "QUIT\r\n");
                    fclose($socket);
                    if ($greeting === false || $response === false || !preg_match('/^2|^3/', trim($response))) {
                        throw new RuntimeException('Der SMTP-Server hat keine gültige Antwort geliefert.');
                    }
                }
