<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

use Src\User\Permission;

$user = $userRepository->findById($session->get('id'));
$authToManageMeetings = $user->isAdmin || $acl->isPermitted($user->id, (int)$session->get('Organization'), Permission::MANAGE_MEETINGS);

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
                    <a href="./newmeeting.php" class="btn btn-primary">
                        <?= $translator->t('meetings.create') ?>
                    </a>
                </div>
            </div>

            <?php

            $anzahl = $_GET['anzahl'] ?? 10;

            if (isset($_GET['showAll'])) {
                $meetings = $meetingRepository->findAll(true);
            } else {
                $meetings = $meetingRepository->findOpenMeetings((int) $anzahl);
            }


            if (isset($_GET['filterBy'])) {

                $filterBy = $_GET['filterBy'] ?? null;

                switch ($filterBy) {
                    case 'title':
                        $filterTerm = $_GET['filterTerm'] ?? '';
                        $meetings = array_filter($meetings, function ($b) use ($filterTerm) {
                            return $b->title === $filterTerm;
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
                                <input type="text" class="form-control" name="title" placeholder="Titel"
                                    value="<?= htmlspecialchars($_GET['title'] ?? '') ?>">
                            </div>

                            <div class="col-12 col-md-6 col-xl-3">
                                <label class="form-label"><?= $translator->t('meetings.date'); ?></label>
                                <input type="datetime-local" class="form-control" name="date"
                                    value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
                            </div>

                            <div class="col-8 col-md-6 col-xl-2">
                                <label class="form-label"><?= $translator->t('meetings.location'); ?></label>
                                <input type="text" class="form-control" name="location" placeholder="Ort"
                                    value="<?= htmlspecialchars($_GET['location'] ?? '') ?>">
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

            <?php foreach ($meetings as $meeting) { ?>

                <?php
                $datum = (new DateTime($meeting->date))->format('d.m.Y');
                if ($meeting->startTime && $meeting->endTime) {
                    $zeiten = (new DateTime($meeting->startTime))->format('H:i') . ' - ' . (new DateTime($meeting->endTime))->format('H:i');
                    $dauer = (new DateTime($meeting->endTime))->getTimestamp() - (new DateTime($meeting->startTime))->getTimestamp();
                    $dauer = round($dauer / 60);
                } else {
                    $zeiten = '';
                    $dauer = '';
                }
                // $zeiten = (new DateTime($meeting->startTime))->format('H:i') . ' - ' . (new DateTime($meeting->endTime))->format('H:i');
                $ersteller = $userRepository->findById((int) $meeting->creatorUserId);
                ?>

                <div class="card mb-3 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>
                            <h3 class="card-title mb-0">
                                <a href="meeting.php?id=<?= $meeting->id; ?>" class="text-reset">
                                    <?= htmlspecialchars($meeting->title); ?>
                                </a>
                            </h3>
                        </span>
                        <h3>
                            <?php if ($meeting->status === 'planned'): ?>
                                <span class="badge bg-primary-lt"><?= $translator->t('meetings.status.planned'); ?></span>
                            <?php elseif ($meeting->status === 'live'): ?>
                                <span class="badge bg-success-lt"><?= $translator->t('meetings.status.active'); ?></span>
                            <?php elseif ($meeting->status === 'finished'): ?>
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
                                <div class="meeting-meta-value"><?= htmlspecialchars($meeting->location); ?></div>
                            </div>

                            <div class="meeting-meta-item">
                                <div class="meeting-meta-label"><?= $translator->t('meetings.createdBy'); ?></div>
                                <div class="meeting-meta-value"><?= htmlspecialchars($ersteller->firstName . ' ' . $ersteller->lastName); ?></div>
                            </div>

                            <div class="meeting-meta-item">
                                <div class="meeting-meta-label"><?= $translator->t('meetings.createdAt'); ?></div>
                                <div class="meeting-meta-value"><?= htmlspecialchars($meeting->createdAt); ?></div>
                            </div>

                            <div class="meeting-meta-item">
                                <div class="meeting-meta-label"><?= $translator->t('meetings.updatedAt'); ?></div>
                                <div class="meeting-meta-value"><?= htmlspecialchars($meeting->updatedAt); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php }; ?>

        </div>

    </div>


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