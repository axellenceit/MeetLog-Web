<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

// Ein Error kann auch im abgemeldeten Zustand angezeigt werden
// if (!$auth->isLoggedIn()) {
//     $redirect->to('login.php');
// }

$errorColor = $flash->get('errorColor') ?? 'orange';
$errorCode = $flash->get('errorCode') ?? 500;
$errorMsg = $flash->get('errorMsg') ?? 'Upps...hier ist was schief gegangen';

// sanitize inputs for embedding into SVG
$svgColor = preg_replace('/[^#A-Za-z0-9\-]/', '', (string)$errorColor);
$svgCode = is_numeric($errorCode) ? (int)$errorCode : htmlspecialchars((string)$errorCode);

// build simple SVG image as data URI (colored background with centered error code)
$svg = sprintf(
    '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><rect width="100%%" height="100%%" rx="8" ry="8" fill="%s"/><text x="50%%" y="50%%" dominant-baseline="middle" text-anchor="middle" font-family="Roboto, Arial, sans-serif" font-size="36" fill="white">%s</text></svg>',
    htmlspecialchars($svgColor, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars((string)$svgCode, ENT_QUOTES, 'UTF-8')
);
$svgDataUri = 'data:image/svg+xml;utf8,' . rawurlencode($svg);

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($translator->getLocale()) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeetLog</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
</head>

<body class="d-flex justify-content-center align-items-center min-vh-100">
    <div class="card">
        <div class="login-card-header">
            <center><img src="<?= $svgDataUri; ?>" class="img-fluid mt-2 mb-2" alt="Fehler"></center>
        </div>

        <div class="card-body">
            <h3><?= $errorMsg; ?></h3>
        </div>

        <div class="card-footer text-end">
            <a href="index.php"> -> zurück zur Anwendung</a>
        </div>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

</html>