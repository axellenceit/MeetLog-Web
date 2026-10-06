<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeetLog</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <link rel="icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
    <style>
        .profile-photo {
            position: relative;
            display: inline-block;
            width: 64px;
            height: 64px;
        }

        .profile-photo img {
            display: block;
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 6px;
        }

        .delete-photo-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 20px;
            height: 20px;
            border: none;
            border-radius: 50%;
            background: rgba(220, 53, 69, 0.95);
            color: #fff;
            font-size: 14px;
            line-height: 20px;
            padding: 0;
            cursor: pointer;
            z-index: 2;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease;
        }

        .profile-photo:hover .delete-photo-btn,
        .profile-photo:focus-within .delete-photo-btn {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>

<body>

    <div class="page">
        <div class="sticky-top mb-2">
            <?php include_once 'header.php'; ?>
        </div>

        <div class="container-xl">
            <div class="row g-3">

                <!-- Profilangaben: 8 von 12 Spalten -->
                <div class="col-8">
                    <form action="." method="post">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">
                                    <?= htmlspecialchars($translator->t('profile.root'), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                            <div class="card-body">

                                <div class="row g-3 mb-3">
                                    <div class="col">
                                        <label class="form-label">
                                            <?= htmlspecialchars($translator->t('profile.vorname'), ENT_QUOTES, 'UTF-8') ?>
                                        </label>
                                        <input type="text" name="firstName" id="firstName" class="form-control"
                                            value="<?= htmlspecialchars((string) ($user->firstName ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col">
                                        <label class="form-label">
                                            <?= htmlspecialchars($translator->t('profile.nachname'), ENT_QUOTES, 'UTF-8') ?>
                                        </label>
                                        <input type="text" name="lastName" id="lastName" class="form-control"
                                            value="<?= htmlspecialchars((string) ($user->lastName ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col">
                                        <label class="form-label">
                                            <?= htmlspecialchars($translator->t('profile.mailaddress'), ENT_QUOTES, 'UTF-8') ?>
                                        </label>
                                        <input type="text" name="mail" id="mail" class="form-control"
                                            value="<?= htmlspecialchars((string) ($session->get('Mail') ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col">
                                        <label class="form-label">
                                            <?= htmlspecialchars($translator->t('profile.password'), ENT_QUOTES, 'UTF-8') ?>
                                        </label>
                                        <input type="password" name="password" id="password" class="form-control"
                                            autocomplete="off" value="">
                                    </div>
                                </div>

                            </div>
                            <div class="card-footer">
                                <div class="text-end">
                                    <a href="<?= htmlspecialchars($currentURL, ENT_QUOTES, 'UTF-8') ?>"
                                        class="btn btn-1">
                                        <?= htmlspecialchars($translator->t('common.cancel'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-2">
                                        <?= htmlspecialchars($translator->t('common.save'), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Profilfoto: 4 von 12 Spalten -->
                <div class="col-4">
                    <form action="./actions.php?action=uploadProfilePhoto" method="post" enctype="multipart/form-data">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">
                                    <?= htmlspecialchars($translator->t('profile.photo'), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($user->photo == ''): ?>
                                    <input name="file" type="file" class="form-control"
                                        placeholder="<?= htmlspecialchars($translator->t('profile.text.photoupload'), ENT_QUOTES, 'UTF-8') ?>" />
                                <?php else: ?>
                                    <div class="profile-photo">
                                        <img src="<?= htmlspecialchars($user->photo, ENT_QUOTES, 'UTF-8') ?>"
                                            alt="Profilfoto">
                                        <button type="button" class="delete-photo-btn" title="Profilfoto löschen"
                                            onclick="deleteProfilePhoto()">
                                            ×
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <div class="text-end">
                                    <a href="<?= htmlspecialchars($currentURL, ENT_QUOTES, 'UTF-8') ?>"
                                        class="btn btn-1">
                                        <?= htmlspecialchars($translator->t('common.cancel'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-2">
                                        <?= htmlspecialchars($translator->t('common.save'), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

    <script>
        function deleteProfilePhoto() {
            location.href = "./actions.php?action=deleteProfilePhoto";
        }
    </script>

    <?php include_once 'footer.php'; ?>

</body>

</html>