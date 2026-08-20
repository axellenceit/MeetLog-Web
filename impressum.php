<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

require_once __DIR__ . '/version.php';

// Impressum ist bewusst öffentlich erreichbar (kein Login erforderlich).

// Angaben zur App und zum Entwickler hier pflegen:
$appName = 'MeetLog';
$appVersion = $currentVersion;
$appUrl = 'https://github.com/axellenceit/MeetLog/wiki';
$appGithub = 'https://github.com/axellenceit/MeetLog';

$developerName = 'Axel Hoga';
$developerLinkedIn = 'https://www.linkedin.com/in/axel-hoga-datamanager';
$developerGithub = 'https://github.com/axellenceit';

// Verwendete Bibliotheken mit Versionsnummer.
$libraries = [
    ['name' => 'Tabler (Admin-Template)', 'version' => 'latest (CDN)', 'url' => 'https://tabler.io'],
    ['name' => 'Tabler Icons', 'version' => 'latest (CDN, Bestandteil von Tabler)', 'url' => 'https://tabler-icons.io'],
    ['name' => 'HugeRTE', 'version' => '1.0.10', 'url' => 'https://hugerte.org'],
    ['name' => 'FullCalendar', 'version' => '6.1.20', 'url' => 'https://fullcalendar.io'],
    ['name' => 'PHPMailer', 'version' => '7.0.2', 'url' => 'https://github.com/PHPMailer/PHPMailer'],
    ['name' => 'TCPDF', 'version' => '6.10.1', 'url' => 'https://tcpdf.org'],
];

?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impressum - <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
</head>

<body>

    <div class="page">
        <div class="container-xl py-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <a href="./index.php" class="link-secondary">&larr; Zurück zur Anwendung</a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h2 class="mb-4">Impressum</h2>

                            <h3 class="h4">Angaben zur App</h3>
                            <p class="mb-4">
                                <strong><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                (Version <?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8'); ?>)
                                <a class="btn btn-website" href="<?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                    Wiki / Dokumentation</a>
                                </a>
                                <a class="btn btn-github" href="<?= htmlspecialchars($appGithub, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><!-- Download SVG icon from http://tabler.io/icons/icon/brand-github -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                        <path d="M9 19c-4.3 1.4 -4.3 -2.5 -6 -3m12 5v-3.5c0 -1 .1 -1.4 -.5 -2c2.8 -.3 5.5 -1.4 5.5 -6a4.6 4.6 0 0 0 -1.3 -3.2a4.2 4.2 0 0 0 -.1 -3.2s-1.1 -.3 -3.5 1.3a12.3 12.3 0 0 0 -6.2 0c-2.4 -1.6 -3.5 -1.3 -3.5 -1.3a4.2 4.2 0 0 0 -.1 3.2a4.6 4.6 0 0 0 -1.3 3.2c0 4.6 2.7 5.7 5.5 6c-.6 .6 -.6 1.2 -.5 2v3.5"></path>
                                    </svg>
                                    Github-Repository</a>
                            </p>

                            <h3 class="h4">Verantwortlich / Entwickler</h3>
                            <p class="mb-4">
                                <strong><?= htmlspecialchars($developerName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <a class="btn btn-linkedin" href="<?= htmlspecialchars($developerLinkedIn, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><!-- Download SVG icon from http://tabler.io/icons/icon/brand-linkedin -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                        <path d="M8 11v5"></path>
                                        <path d="M8 8v.01"></path>
                                        <path d="M12 16v-5"></path>
                                        <path d="M16 16v-3a2 2 0 1 0 -4 0"></path>
                                        <path d="M3 7a4 4 0 0 1 4 -4h10a4 4 0 0 1 4 4v10a4 4 0 0 1 -4 4h-10a4 4 0 0 1 -4 -4z"></path>
                                    </svg>
                                    Linkedin</a>
                                <a class="btn btn-github" href="<?= htmlspecialchars($developerGithub, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><!-- Download SVG icon from http://tabler.io/icons/icon/brand-github -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                        <path d="M9 19c-4.3 1.4 -4.3 -2.5 -6 -3m12 5v-3.5c0 -1 .1 -1.4 -.5 -2c2.8 -.3 5.5 -1.4 5.5 -6a4.6 4.6 0 0 0 -1.3 -3.2a4.2 4.2 0 0 0 -.1 -3.2s-1.1 -.3 -3.5 1.3a12.3 12.3 0 0 0 -6.2 0c-2.4 -1.6 -3.5 -1.3 -3.5 -1.3a4.2 4.2 0 0 0 -.1 3.2a4.6 4.6 0 0 0 -1.3 3.2c0 4.6 2.7 5.7 5.5 6c-.6 .6 -.6 1.2 -.5 2v3.5"></path>
                                    </svg>
                                    Github</a>
                            </p>

                            <h3 class="h4">Verwendete Bibliotheken</h3>
                            <div class="table-responsive">
                                <table class="table table-vcenter">
                                    <thead>
                                        <tr>
                                            <th>Bibliothek</th>
                                            <th>Version</th>
                                            <th>Link</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($libraries as $library): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($library['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= htmlspecialchars($library['version'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td>
                                                    <a href="<?= htmlspecialchars($library['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                        <?= htmlspecialchars($library['url'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

</html>