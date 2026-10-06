<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

if (!$session->get('IsAdmin')) {
    $redirect->to('index.php');
}

$settings = $config->all();

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
        .config-meta {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .config-meta-item {
            padding: .75rem 1rem;
            border: 1px solid var(--tblr-border-color, #dce1e7);
            border-radius: .75rem;
            background: var(--tblr-bg-surface-secondary, #f6f8fb);
        }

        .config-meta-label {
            font-size: .875rem;
            color: var(--tblr-secondary);
            margin-bottom: .25rem;
        }

        .config-meta-value {
            font-weight: 600;
            word-break: break-word;
        }

        @media (min-width: 768px) {
            .config-meta {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1200px) {
            .config-meta {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
    </style>
</head>

<body>

    <div class="page">
        <div class="sticky-top mb-2">
            <?php include_once 'header.php'; ?>
        </div>

        <div class="container-xl">

            <?php foreach ($settings as $section => $sectionValues) { ?>

                <form action="actions.php?action=saveConfig" method="post">
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>
                                <h3 class="card-title mb-0">
                                    <?= htmlspecialchars($translator->t('settings.' . $section)); ?>
                                </h3>
                            </span>
                        </div>

                        <div class="card-body">

                            <div class="config-meta">
                                <?php foreach ($sectionValues as $name => $value) { ?>
                                    <div class="config-meta-item">
                                        <div class="config-meta-label"><?= htmlspecialchars($translator->t('settings.sections.' . $section . '.' . $name)); ?></div>
                                        <div class="config-meta-value">
                                            <?php if (str_contains($name, 'pass')) { ?>
                                                <div class="input-group input-group-flat">
                                                    <input
                                                        type="password"
                                                        class="form-control"
                                                        id="update_<?= $section . '_' . $name; ?>"
                                                        name="update_<?= $section . '_' . $name; ?>"
                                                        value="<?= $value; ?>">

                                                    <span class="input-group-text">
                                                        <button
                                                            type="button"
                                                            class="btn btn-link p-0 link-secondary"
                                                            aria-label="Passwort anzeigen"
                                                            onclick="togglePassword('update<?= $section . $name; ?>', this)">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                                                <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                                            </svg>
                                                        </button>
                                                    </span>
                                                </div>
                                            <?php } elseif (str_contains($name, 'toggle')) { ?>
                                                <!-- Hier ist ein verstecktes Feld, damit immer ein Wert übermittelt wird. Wenn der eigentliche Toggle checked ist, überschreibt dieser den versteckten Wert -->
                                                <input type="hidden" id="update_<?= $section . '_' . $name; ?>" name="update_<?= $section . '_' . $name; ?>" value="0">

                                                <label class="form-check form-switch form-switch-2 mb-2 mt-2">
                                                    <input class="form-check-input" type="checkbox" id="update_<?= $section . '_' . $name; ?>" name="update_<?= $section . '_' . $name; ?>" <?= (($value ?? null) ? 'checked' : ''); ?>>
                                                </label>
                                            <?php } else { ?>
                                                <input type=" text" class="form-control" id="update_<?= $section . '_' . $name; ?>" name="update_<?= $section . '_' . $name; ?>" value="<?= $value; ?>">
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>

                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <?php if ($section === 'database') { ?>
                                <button type="button" class="btn btn-success" id="testDB" name="testDB">
                                    <?= $translator->t('common.test'); ?>
                                </button>
                            <?php } elseif ($section === 'mail') { ?>
                                <button type="button" class="btn btn-success" id="testMail" name="testMail">
                                    <?= $translator->t('common.test'); ?>
                                </button>
                            <?php } ?>

                            <button type="submit" class="btn btn-primary" id="updateConfigButton" name="updateConfigButton"><?= $translator->t('common.save'); ?></button>
                        </div>
                    </div>
                </form>

            <?php } ?>

        </div>

    </div>

    <?php include_once 'footer.php'; ?>

</body>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

<script>
    function togglePassword(inputId, button) {
        const field = document.getElementById(inputId);

        const eyeSvg = `
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
            </svg>
        `;

        const eyeOffSvg = `
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye-off" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M3 3l18 18"/>
                <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"/>
                <path d="M9.88 5.09a9.77 9.77 0 0 1 2.12 -.09c3.6 0 6.6 2 9 6a17.55 17.55 0 0 1 -2.16 2.91"/>
                <path d="M6.71 6.72c-2.05 1.36 -3.71 3.15 -4.71 5.28c2.4 4 5.4 6 9 6c1.32 0 2.58 -.27 3.77 -.82"/>
            </svg>
        `;

        if (field.type === 'password') {
            field.type = 'text';
            button.innerHTML = eyeOffSvg;
            button.setAttribute('aria-label', 'Passwort verbergen');
        } else {
            field.type = 'password';
            button.innerHTML = eyeSvg;
            button.setAttribute('aria-label', 'Passwort anzeigen');
        }
    }
</script>

<script>
    document.getElementById('testDB')?.addEventListener('click', async function() {
        const form = this.closest('form');
        const formData = new FormData(form);
        formData.append('type', 'database');

        const response = await fetch('actions.php?action=testConfig', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        alert(result.message);
    });

    document.getElementById('testMail')?.addEventListener('click', async function() {
        const form = this.closest('form');
        const formData = new FormData(form);
        formData.append('type', 'mail');

        const response = await fetch('actions.php?action=testConfig', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        alert(result.message);
    });
</script>

</html>