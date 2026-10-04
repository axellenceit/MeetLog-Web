<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

if (!file_exists(__DIR__ . '/install.done')) {
    $redirect->to('install/index.php');
}

if ($auth->isLoggedIn()) {
    $redirect->to('index.php');
}

$config->reload();
$togglePublicMeeting = (int) ($config->get('general', 'togglePublicMeeting') ?? 0) === 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'login') {
        $user = $auth->login(
            trim($_POST['email'] ?? ''),
            $_POST['password'] ?? ''
        );

        if ($user !== null) {
            $redirect->to('index.php');
        }

        $flash->set('login_error', 'Ungültige E-Mail und/oder Passwort');
    }

    if ($formType === 'public_meeting') {
        $meetingId = trim($_POST['meeting'] ?? '');

        if ($meetingId !== '') {
            $redirect->to('api/getMeetingAsPublic.php?meeting=' . urlencode($meetingId));
        }
    }
}

$loginError = $flash->get('login_error');

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($translator->getLocale()) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeetLog</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <style>
        body {
            min-height: 100vh;
            background: var(--tblr-body-bg, #f6f8fb);
        }

        body.app-login-page {
            min-height: 100vh;
            margin: 0;
            background: #f4f5f7;
        }

        .login-layout {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
        }

        .app-brand {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .app-logo {
            max-width: 320px;
            width: 100%;
            height: auto;
            display: block;
        }

        .login-panel {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .panel-inner {
            padding: 40px;
        }

        @media (max-width: 767.98px) {
            .login-layout {
                padding: 20px;
            }

            .app-logo {
                max-width: 240px;
            }

            .panel-inner {
                padding: 24px;
            }
        }
    </style>
</head>

<body class="app-login-page">
    <div class="login-layout">
        <div class="app-brand text-center mb-4">
            <img src="./img/meetlog_512.png" width="256" height="256" alt="MeetLog" class="app-logo">
        </div>

        <div class="row g-4 <?= !$togglePublicMeeting ? 'justify-content-center' : '' ?>">
            <div class="col-12 col-md-6">
                <form action="" method="post" class="login-panel h-100">
                    <div class="panel-inner">
                        <input type="hidden" name="form_type" value="login">

                        <?php if ($loginError !== ''): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($loginError) ?>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control login-input"
                                placeholder="login"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control login-input"
                                placeholder="password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <?= htmlspecialchars($translator->t('auth.sign_in')) ?>
                        </button>

                        <div class="text-center text-sm">
                            <a href="./password-forgotten.php" class="text-muted fs-5">
                                <?= htmlspecialchars($translator->t('auth.forgotPassword')) ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($togglePublicMeeting): ?>
                <div class="col-12 col-md-6">
                    <form action="" method="post" id="public-meeting-form" class="login-panel h-100">
                        <input type="hidden" name="form_type" value="public_meeting">

                        <div class="panel-inner d-flex flex-column justify-content-center h-100">
                            <div class="text-center mb-4">
                                <h2 class="h1 mb-2">Öffentliche Besprechung</h2>
                                <p class="text-muted mb-0">Gib hier eine öffentliche Besprechungs-ID ein.</p>
                            </div>

                            <div class="mb-3">
                                <label for="meeting" class="form-label">Besprechungs-ID</label>
                                <input
                                    type="text"
                                    id="meeting"
                                    name="meeting"
                                    class="form-control"
                                    placeholder="z. B. ABC123"
                                    required>
                            </div>

                            <button type="submit" class="btn btn-outline-primary w-100">
                                Öffentliche Besprechung öffnen
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>