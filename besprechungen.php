<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

use Src\User\Permission;

$user = $userRepository->findById($session->get('id'));
$authToManageMeetings = $user->istAdmin || $acl->isPermitted($user->id, (int)$session->get('Organisation'), Permission::MANAGE_MEETINGS);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeetLog</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">

    <style>
        .meeting-meta {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .meeting-meta-item {
            padding: .75rem 1rem;
            border: 1px solid var(--tblr-border-color, #dce1e7);
            border-radius: .75rem;
            background: var(--tblr-bg-surface-secondary, #f6f8fb);
        }

        .meeting-meta-label {
            font-size: .875rem;
            color: var(--tblr-secondary);
            margin-bottom: .25rem;
        }

        .meeting-meta-value {
            font-weight: 600;
            word-break: break-word;
        }

        @media (min-width: 768px) {
            .meeting-meta {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1200px) {
            .meeting-meta {
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

        <div class="container-xl py-3">

            <div class="d-flex justify-content-between align-items-center mb-3 page-title-row">
                <div>
                    <h2 class="page-title mb-0">
                        <?= htmlspecialchars($translator->t('nav.meetings'), ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-meeting-create">
                        <?= $translator->t('meetings.create') ?>
                    </button>
                </div>
            </div>

            <?php

            $anzahl = $_GET['anzahl'] ?? 10;

            if (isset($_GET['showAll'])) {
                $besprechungen = $besprechungRepository->findAll(true);
            } else {
                $besprechungen = $besprechungRepository->findNextMeetings((int) $anzahl);
            }


            if (isset($_GET['filterBy'])) {

                $filterBy = $_GET['filterBy'] ?? null;

                switch ($filterBy) {
                    case 'titel':
                        $filterTerm = $_GET['filterTerm'] ?? '';
                        $besprechungen = array_filter($besprechungen, function ($b) use ($filterTerm) {
                            return $b->titel === $filterTerm;
                        });

                        break;

                    default:
                        break;
                }
            }
            ?>
            <form action="" method="get" class="mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-6 col-xl-3">
                                <label class="form-label"><?= $translator->t('meetings.title'); ?></label>
                                <input type="text" class="form-control" name="titel" placeholder="Titel"
                                    value="<?= htmlspecialchars($_GET['titel'] ?? '') ?>">
                            </div>

                            <div class="col-12 col-md-6 col-xl-3">
                                <label class="form-label"><?= $translator->t('meetings.date'); ?></label>
                                <input type="datetime-local" class="form-control" name="datum"
                                    value="<?= htmlspecialchars($_GET['datum'] ?? '') ?>">
                            </div>

                            <div class="col-8 col-md-6 col-xl-2">
                                <label class="form-label"><?= $translator->t('meetings.location'); ?></label>
                                <input type="text" class="form-control" name="ort" placeholder="Ort"
                                    value="<?= htmlspecialchars($_GET['ort'] ?? '') ?>">
                            </div>

                            <div class="col-6 col-md-3 col-xl-1">
                                <label class="form-label"><?= $translator->t('common.count'); ?></label>
                                <input type="number" min="1" max="100" class="form-control" name="anzahl"
                                    value="<?= htmlspecialchars((string)($_GET['anzahl'] ?? 10)) ?>">
                            </div>

                            <div class="col-6 col-md-3 col-xl-1">
                                <label class="form-label"><?= $translator->t('meetings.showAll'); ?></label>
                                <label class="form-check form-switch form-switch-2">
                                    <input class="form-check-input" type="checkbox" name="showAllSwitch" id="showAllSwitch" <?= (($_GET['showAll'] ?? null) ? 'checked' : ''); ?>>
                                </label>
                            </div>

                            <div class="col-6 col-md-3 col-xl-2 d-grid">
                                <button type="submit" class="btn btn-primary">Filtern</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <?php foreach ($besprechungen as $besprechung) { ?>

                <?php
                $datum = (new DateTime($besprechung->datum))->format('d.m.Y');
                if ($besprechung->beginn && $besprechung->ende) {
                    $zeiten = (new DateTime($besprechung->beginn))->format('H:i') . ' - ' . (new DateTime($besprechung->ende))->format('H:i');
                    $dauer = (new DateTime($besprechung->ende))->getTimestamp() - (new DateTime($besprechung->beginn))->getTimestamp();
                    $dauer = round($dauer / 60);
                } else {
                    $zeiten = '';
                    $dauer = '';
                }
                // $zeiten = (new DateTime($besprechung->beginn))->format('H:i') . ' - ' . (new DateTime($besprechung->ende))->format('H:i');
                $ersteller = $userRepository->findById((int) $besprechung->erstellerUserId);
                ?>

                <div class="card mb-3 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>
                            <h3 class="card-title mb-0">
                                <a href="besprechung.php?id=<?= $besprechung->id; ?>" class="text-reset">
                                    <?= htmlspecialchars($besprechung->titel); ?>
                                </a>
                            </h3>
                        </span>
                        <h3>
                            <?php if ($besprechung->status === 'geplant'): ?>
                                <span class="badge bg-primary-lt"><?= $translator->t('meetings.status.planned'); ?></span>
                            <?php elseif ($besprechung->status === 'aktiv'): ?>
                                <span class="badge bg-success-lt"><?= $translator->t('meetings.status.active'); ?></span>
                            <?php elseif ($besprechung->status === 'beendet'): ?>
                                <span class="badge bg-dark-lt"><?= $translator->t('meetings.status.finished'); ?></span>
                            <?php endif; ?>
                        </h3>
                    </div>

                    <div class="card-body">


                        <div class="meeting-meta">
                            <div class="meeting-meta-item">
                                <div class="meeting-meta-label"><?= $translator->t('meetings.date'); ?></div>
                                <div class="meeting-meta-value"><?= htmlspecialchars($datum); ?></div>
                            </div>

                            <div class="meeting-meta-item">
                                <div class="meeting-meta-label"><?= $translator->t('meetings.time'); ?></div>
                                <div class="meeting-meta-value"><?= htmlspecialchars($zeiten); ?></div>
                            </div>

                            <div class="meeting-meta-item">
                                <div class="meeting-meta-label"><?= $translator->t('meetings.location'); ?></div>
                                <div class="meeting-meta-value"><?= htmlspecialchars($besprechung->ort); ?></div>
                            </div>

                            <div class="meeting-meta-item">
                                <div class="meeting-meta-label"><?= $translator->t('meetings.createdBy'); ?></div>
                                <div class="meeting-meta-value"><?= htmlspecialchars($ersteller->vorname . ' ' . $ersteller->nachname); ?></div>
                            </div>

                            <div class="meeting-meta-item">
                                <div class="meeting-meta-label"><?= $translator->t('meetings.createdAt'); ?></div>
                                <div class="meeting-meta-value"><?= htmlspecialchars($besprechung->createdAt); ?></div>
                            </div>

                            <div class="meeting-meta-item">
                                <div class="meeting-meta-label"><?= $translator->t('meetings.updatedAt'); ?></div>
                                <div class="meeting-meta-value"><?= htmlspecialchars($besprechung->updatedAt); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php }; ?>

        </div>

    </div>

    <!-- Modaler Dialog für Besprechungen erstellen -->
    <?php if ($authToManageMeetings) { ?>
        <div class="modal modal-blur fade" id="modal-meeting-create" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form method="post" action="actions.php?action=create_meeting">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= $translator->t('meetings.create'); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row mb-2">
                                <div class="col-12 col-xl-6">
                                    <label class="form-label" for="titel-create"><?= $translator->t('meetings.title'); ?></label>
                                    <input type="text" name="titel" id="titel-create" class="form-control">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 col-md-4">
                                    <label class="form-label" for="datum-create"><?= $translator->t('meetings.date'); ?></label>
                                    <input type="date" name="datum" id="datum-create" class="form-control">
                                </div>
                                <div class="col-4 col-md-4">
                                    <label class="form-label" for="beginn-create"><?= $translator->t('meetings.start'); ?></label>
                                    <select name="beginn" id="beginn-create" class="form-select">
                                        <option value="">--:--</option>
                                        <?php for ($hour = 0; $hour < 24; $hour++): ?>
                                            <?php for ($minute = 0; $minute < 60; $minute += 15): ?>
                                                <?php
                                                $time = sprintf('%02d:%02d', $hour, $minute);
                                                ?>
                                                <option value="<?= $time; ?>"><?= $time; ?></option>
                                            <?php endfor; ?>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-4 col-md-4">
                                    <label class="form-label" for="ende-create"><?= $translator->t('meetings.end'); ?></label>
                                    <select name="ende" id="ende-create" class="form-select">
                                        <option value="">--:--</option>
                                        <?php for ($hour = 0; $hour < 24; $hour++): ?>
                                            <?php for ($minute = 0; $minute < 60; $minute += 15): ?>
                                                <?php
                                                $time = sprintf('%02d:%02d', $hour, $minute);
                                                ?>
                                                <option value="<?= $time; ?>"><?= $time; ?></option>
                                            <?php endfor; ?>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="ort-create"><?= $translator->t('meetings.location'); ?></label>
                                <input type="text" name="ort" id="ort-create" class="form-control">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn me-auto" data-bs-dismiss="modal"><?= $translator->t('common.cancel'); ?></button>
                            <button type="submit" class="btn btn-primary"><?= $translator->t('common.save'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php include_once 'footer.php'; ?>

</body>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

<script>
    document.getElementById('showAllSwitch').addEventListener('change', function() {
        const url = new URL(window.location.href);

        if (this.checked) {
            url.searchParams.set('showAll', '1');
        } else {
            url.searchParams.delete('showAll');
        }

        window.location.href = url.toString();
    });
</script>

</html>