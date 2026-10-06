<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

use Src\Log\LogRepository;

$logs = $logRepository->findAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeetLog</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <link rel="icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
</head>

<body>

    <div class="page">
        <div class="sticky-top mb-2">
            <?php include_once 'header.php'; ?>
        </div>

        <div class="container-xl">

            <div class="d-flex justify-content-between align-items-center mb-3 page-title-row">
                <div>
                    <h2 class="page-title mb-0">
                        <?= htmlspecialchars($translator->t('nav.logs'), ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                </div>
                <div>
                    <a href="actions.php?action=clear_logs" class="btn btn-danger">
                        <?= htmlspecialchars($translator->t('common.clear_logs'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Zeitstempel</th>
                                <th>Benutzer</th>
                                <th>Aktion</th>
                                <th>Schwere</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">Keine Protokolleinträge vorhanden.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $ts = DateTime::createFromFormat('d.m.Y H:i:s', $log->timestamp, new DateTimeZone('UTC'));
                                            if ($ts === false) {
                                                $ts = new DateTime($log->timestamp, new DateTimeZone('UTC'));
                                            }
                                            $ts->setTimezone(new DateTimeZone('Europe/Berlin'));
                                            echo $ts->format('d.m.Y H:i:s');
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $user = $userRepository->findById($log->userId);
                                            if ($user) {
                                                echo htmlspecialchars($user->firstName . ' ' . $user->lastName, ENT_QUOTES, 'UTF-8');
                                            } else {
                                                echo 'Unbekannt';
                                            }
                                            ?>
                                        </td>
                                        <td><?= $log->action ?></td>
                                        <td>
                                            <?php
                                            switch ($log->severity) {
                                                case 'info':
                                                    echo '<button type="button" class="btn btn-icon btn-primary" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Information"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-info-circle">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                                            <path d="M12 9h.01" />
                                                            <path d="M11 12h1v4h1" />
                                                        </svg></button>';
                                                    break;
                                                case 'warning':
                                                    echo '<button type="button" class="btn btn-icon btn-warning" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Warning"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-alert-circle">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                                            <path d="M12 8v4" />
                                                            <path d="M12 16h.01" />
                                                        </svg></button>';
                                                    break;
                                                case 'error':
                                                    echo '<button type="button" class="btn btn-icon btn-danger" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Error"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-bug">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path d="M9 9v-1a3 3 0 0 1 6 0v1" />
                                                                <path d="M8 9h8a6 6 0 0 1 1 3v3a5 5 0 0 1 -10 0v-3a6 6 0 0 1 1 -3" />
                                                                <path d="M3 13l4 0" />
                                                                <path d="M17 13l4 0" />
                                                                <path d="M12 20l0 -6" />
                                                                <path d="M4 19l3.35 -2" />
                                                                <path d="M20 19l-3.35 -2" />
                                                                <path d="M4 7l3.75 2.4" />
                                                                <path d="M20 7l-3.75 2.4" />
                                                            </svg></button>';
                                                    break;
                                                case 'critical':
                                                    echo '<button type="button" class="btn btn-icon btn-danger" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Critical"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-bell-exclamation">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M15 17h-11a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6a2 2 0 1 1 4 0a7 7 0 0 1 4 6v1.5" />
                                                            <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                                                            <path d="M19 16v3" />
                                                            <path d="M19 22v.01" />
                                                        </svg></button>';
                                                    break;

                                                default:
                                                    echo '<button type="button" class="btn btn-icon btn-secondary" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Unbekannt"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-bell-exclamation">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M15 17h-11a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6a2 2 0 1 1 4 0a7 7 0 0 1 4 6v1.5" />
                                                            <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                                                            <path d="M19 16v3" />
                                                            <path d="M19 22v.01" />
                                                        </svg></button>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <?php include_once 'footer.php'; ?>

</body>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

</html>