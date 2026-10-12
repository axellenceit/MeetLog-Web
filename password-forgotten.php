<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

use Src\Log\Log;

if (!file_exists(__DIR__ . '/install.done')) {
    $redirect->to('install/index.php');
}

if ($auth->isLoggedIn()) {
    $redirect->to('index.php');
}

$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $user = $userRepository->findActiveByEmail($email);

        if ($user !== null) {
            $tempPassword = bin2hex(random_bytes(12));
            $updated = $userRepository->updatePasswordHash((int)$user->id, $auth->hashPassword($tempPassword));

            if ($updated) {
                $displayName = trim((string)($user->firstName ?? '') . ' ' . (string)($user->lastName ?? ''));

                $mail->sendPasswordResetEmail(
                    $email,
                    $displayName !== '' ? $displayName : $email,
                    $email,
                    $tempPassword,
                    (int)($user->defaultOrganization ?? 1)
                );

                $logEntry = new Log(
                    id: null,
                    userId: (int)$user->id,
                    action: 'Passwort zurückgesetzt (Passwort vergessen)',
                    severity: 'info',
                    timestamp: date('Y-m-d H:i:s')
                );
                $logRepository->create($logEntry);
            }
        }
    }

    // Aus Sicherheitsgründen immer die gleiche Erfolgsmeldung zeigen,
    // damit nicht erkennbar ist, ob ein Konto existiert
    $submitted = true;
}

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

        <div class="row g-4 justify-content-center">
            <div class="col-12 col-md-6">
                <form action="" method="post" class="login-panel h-100">
                    <div class="panel-inner">
                        <h2 class="h1 mb-2 text-center"><?= htmlspecialchars($translator->t('auth.forgotPassword')) ?></h2>

                        <?php if ($submitted): ?>
                            <div class="alert alert-success" role="alert">
                                <?= htmlspecialchars($translator->t('auth.reset_password_success')) ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mb-4">
                                <?= htmlspecialchars($translator->t('auth.reset_password_info')) ?>
                            </p>

                            <div class="mb-3">
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="form-control login-input"
                                    placeholder="E-Mail"
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <?= htmlspecialchars($translator->t('auth.reset_password')) ?>
                            </button>
                        <?php endif; ?>

                        <div class="text-center text-sm">
                            <a href="./login.php" class="text-muted fs-5">
                                <?= htmlspecialchars($translator->t('auth.back_to_login')) ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>