<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

use Src\User\Permission;

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

if (!isset($_GET['id'])) {
    $redirect->to('besprechungen.php');
}

$user = $userRepository->findById($session->get('id'));
$authToManageMeetings = $user->istAdmin || $acl->isPermitted($user->id, (int)$session->get('Organisation'), Permission::MANAGE_MEETINGS);

$besprechung = $besprechungRepository->findById((int)($_GET['id']));

if (!$besprechung->istAktiviert) {
    $flash->set('errorMsg', 'Diese Besprechung wurde gelöscht');
    $flash->set('errorColor', 'red');
    $flash->set('errorCode', '403');
    $redirect->to('error.php');
}

$datum = (new DateTime($besprechung->datum))->format('d.m.Y');
if ($besprechung->beginn && $besprechung->ende) {
    $zeiten = (new DateTime($besprechung->beginn))->format('H:i') . ' - ' . (new DateTime($besprechung->ende))->format('H:i');
    $dauer = (new DateTime($besprechung->ende))->getTimestamp() - (new DateTime($besprechung->beginn))->getTimestamp();
    $dauer = round($dauer / 60);
} else {
    $zeiten = '';
    $dauer = '';
}

$tops = $besprechungspunktRepository->findByBesprechungId($besprechung->id);

$teilnehmer = $besprechungsteilnehmerRepository->findByBesprechungId($besprechung->id);
$anwesendeTeilnehmer = array_filter($teilnehmer, function ($t) {
    return $t->istAnwesend;
});

$teilnehmerPersonIds = array_filter(array_map(static fn($t) => $t->person, $teilnehmer));
$verfuegbarePersonen = array_filter(
    $personRepository->findAll(),
    static fn($p) => !in_array($p->id, $teilnehmerPersonIds, true)
);

$tasks = $taskRepository->findByBesprechungId($besprechung->id);

$anhaenge = $besprechungsanhangRepository->findByBesprechungId($besprechung->id);

$alleBenutzer = $userRepository->findAll(true);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeetLog</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/hugerte@1.0.10/skins/ui/oxide/content.min.css">-->
    <style>
        html {
            scroll-padding-top: 180px;
        }

        .meeting-sticky-header {
            z-index: 1020;
        }

        .meeting-title-card h1 {
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
            line-height: 1.15;
        }

        .collapse-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: inherit;
            text-decoration: none;
        }

        .collapse-toggle .chevron {
            display: inline-flex;
            transition: transform 0.2s ease;
        }

        .collapse-toggle[aria-expanded="true"] .chevron {
            transform: rotate(180deg);
        }

        .participant-actions .btn.disabled {
            border-color: transparent;
        }

        @media (max-width: 575.98px) {
            .meeting-title-card h1 {
                font-size: 1.75rem;
            }

            .participant-item {
                align-items: flex-start !important;
            }

            .participant-content {
                width: 100%;
                align-items: flex-start !important;
                gap: 0.75rem !important;
            }

            .participant-details {
                min-width: 0;
                flex: 1 1 100%;
            }

            .participant-actions {
                width: 100%;
                margin-left: 0 !important;
                justify-content: flex-start;
                display: grid !important;
                grid-template-columns: repeat(3, minmax(2.5rem, 1fr));
                gap: 0.5rem;
            }

            .participant-actions .vr {
                display: none;
            }

            .participant-actions .btn {
                justify-content: center;
            }
        }

        @media (min-width: 576px) {
            .participant-content {
                width: 100%;
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

            <div class="row g-3 align-items-stretch">

                <div class="card mb-2">
                    <div class="card-body py-3">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">

                            <div class="d-flex align-items-center gap-3 min-w-0">
                                <a href="besprechungen.php"
                                    title="<?= $translator->t('meetings.back_to_overview'); ?>"
                                    class="btn btn-icon btn-outline-primary flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-arrow-left">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                        <line x1="5" y1="12" x2="9" y2="16" />
                                        <line x1="5" y1="12" x2="9" y2="8" />
                                    </svg>
                                </a>

                                <div class="min-w-0">
                                    <h1 class="mb-0"><?= htmlspecialchars($besprechung->titel, ENT_QUOTES, 'UTF-8'); ?></h1>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap justify-content-start justify-content-lg-end gap-2">
                                <?php if ($authToManageMeetings) { ?>

                                    <?php if ($besprechung->istVeröffentlicht) { ?>
                                        <button type="button" class="btn btn-outline-warning <?php if ($besprechung->status === 'beendet' && !$user->istAdmin) {
                                                                                                    echo 'disabled';
                                                                                                } ?>" data-bs-target="#unpublishMeetingModal" data-bs-toggle="modal">
                                            <?= $translator->t('meetings.unpublish'); ?>
                                        </button>
                                    <?php } else { ?>
                                        <button type="button" class="btn btn-outline-success <?php if ($besprechung->status === 'beendet' && !$user->istAdmin) {
                                                                                                    echo 'disabled';
                                                                                                } ?>" title="<?= $translator->t('meetings.publish'); ?>" data-bs-toggle="modal" data-bs-target="#publishMeetingModal">
                                            <?= $translator->t('meetings.publish'); ?>
                                        </button>
                                    <?php } ?>

                                    <button type="button"
                                        class="btn btn-outline-primary <?php if ($besprechung->status === 'beendet' && !$user->istAdmin) {
                                                                            echo 'disabled';
                                                                        } ?>"
                                        title="<?= $translator->t('meetings.edit'); ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editMeeting">
                                        <?= $translator->t('meetings.edit'); ?>
                                    </button>

                                    <button type="button"
                                        class="btn btn-outline-danger <?php if ($besprechung->status === 'beendet' && !$user->istAdmin) {
                                                                            echo 'disabled';
                                                                        } ?>"
                                        title="<?= $translator->t('meetings.delete'); ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteMeeting">
                                        <?= $translator->t('meetings.delete'); ?>
                                    </button>

                                <?php } ?>

                                <div class="vr"></div>

                                <button type="button"
                                    class="btn btn-outline-primary <?php if ($besprechung->status != 'geplant') {
                                                                        echo 'disabled';
                                                                    } ?>"
                                    title="<?= $translator->t('meetings.participants.sentInvitation'); ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#sendInvitation">
                                    <?= $translator->t('meetings.participants.sentInvitation'); ?>
                                </button>

                                <a href="#"
                                    class="btn btn-outline-primary"
                                    title="<?= $translator->t('meetings.printToPDF'); ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#printToPDF">
                                    <?= $translator->t('meetings.printToPDF'); ?>
                                </a>

                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row g-3 align-items-stretch">

                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?= $translator->t('meetings.frame'); ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 mb-2">
                                <div class="col-5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-book-2">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M19 4v16h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12" />
                                        <path d="M19 16h-12a2 2 0 0 0 -2 2" />
                                        <path d="M9 8h6" />
                                    </svg><strong> <?= $translator->t('meetings.status.root'); ?></strong>
                                </div>
                                <div class="col-7">
                                    <?php
                                    if ($besprechung->status === 'geplant') {
                                        echo '<span class="badge bg-secondary text-white">' . $translator->t('meetings.status.planned') . '</span>';
                                    } elseif ($besprechung->status === 'aktiv') {
                                        echo '<span class="badge bg-success text-white">' . $translator->t('meetings.status.active') . '</span>';
                                    } elseif ($besprechung->status === 'beendet') {
                                        echo '<span class="badge bg-dark text-white">' . $translator->t('meetings.status.finished') . '</span>';
                                    }
                                    ?>
                                    <?php if ($besprechung->istVeröffentlicht) { ?>
                                        <span class="badge bg-success text-white"> <?= $translator->t('meetings.published'); ?></span>
                                    <?php } else { ?>
                                        <span class="badge bg-danger text-white"> <?= $translator->t('meetings.unpublished'); ?></span>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12" />
                                        <path d="M16 3v4" />
                                        <path d="M8 3v4" />
                                        <path d="M4 11h16" />
                                        <path d="M11 15h1" />
                                        <path d="M12 15v3" />
                                    </svg><strong> <?= $translator->t('meetings.date'); ?>:</strong>
                                </div>
                                <div class="col-7">
                                    <?= $datum; ?>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-clock">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                        <path d="M12 7v5l3 3" />
                                    </svg><strong> <?= $translator->t('meetings.time'); ?>:</strong>
                                </div>
                                <div class="col-7">
                                    <?= $zeiten; ?>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-hourglass-empty">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M6 20v-2a6 6 0 1 1 12 0v2a1 1 0 0 1 -1 1h-10a1 1 0 0 1 -1 -1" />
                                        <path d="M6 4v2a6 6 0 1 0 12 0v-2a1 1 0 0 0 -1 -1h-10a1 1 0 0 0 -1 1" />
                                    </svg><strong> <?= $translator->t('meetings.duration'); ?>:</strong>
                                </div>
                                <div class="col-7">
                                    <?= $dauer; ?> <?= $translator->t('common.minutes'); ?>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-map-pin">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                                        <path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0" />
                                    </svg><strong> <?= $translator->t('meetings.location'); ?>:</strong>
                                </div>
                                <div class="col-7">
                                    <?= $besprechung->ort; ?>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-link">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M9 15l6 -6" />
                                        <path d="M11 6l.463 -.536a5 5 0 0 1 7.071 7.072l-.534 .464" />
                                        <path d="M13 18l-.397 .534a5.068 5.068 0 0 1 -7.127 0a4.972 4.972 0 0 1 0 -7.071l.524 -.463" />
                                    </svg><strong> <?= $translator->t('meetings.link'); ?>:</strong>
                                </div>
                                <div class="col-7">
                                    <?php if ($besprechung->istVeröffentlicht) { ?>
                                        <a href="<?= $config->get('general', 'url'); ?>api/getMeetingAsPublic.php?besprechung=<?= $besprechung->public_code; ?>" target="_blank"><?= $besprechung->public_code; ?></a>

                                    <?php } else { ?>
                                        -
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-event">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2l0 -12" />
                                        <path d="M16 3l0 4" />
                                        <path d="M8 3l0 4" />
                                        <path d="M4 11l16 0" />
                                        <path d="M8 15h2v2h-2l0 -2" />
                                    </svg><strong> <?= $translator->t('meetings.createdAt'); ?>:</strong>
                                </div>
                                <div class="col-7">
                                    <?= $besprechung->createdAt; ?>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-screen">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M19.03 17.818a3 3 0 0 0 1.97 -2.818v-8a3 3 0 0 0 -3 -3h-12a3 3 0 0 0 -3 3v8c0 1.317 .85 2.436 2.03 2.84" />
                                        <path d="M10 14a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                        <path d="M8 21a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2" />
                                    </svg><strong> <?= $translator->t('meetings.createdBy'); ?>:</strong>
                                </div>
                                <div class="col-7">
                                    <?= $userRepository->findByID($besprechung->erstellerUserId)->vorname . ' ' . $userRepository->findByID($besprechung->erstellerUserId)->nachname; ?>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-event">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2l0 -12" />
                                        <path d="M16 3l0 4" />
                                        <path d="M8 3l0 4" />
                                        <path d="M4 11l16 0" />
                                        <path d="M8 15h2v2h-2l0 -2" />
                                    </svg><strong> <?= $translator->t('meetings.updatedAt'); ?>:</strong>
                                </div>
                                <div class="col-7">
                                    <?= $besprechung->updatedAt; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?= $translator->t('meetings.participants.root'); ?></h3>
                            <div class="card-actions">
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#blankParticipantList">
                                    <?= $translator->t('meetings.participants.blanklist'); ?>
                                </button>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addParticipantModal">
                                    <?= $translator->t('meetings.participants.add'); ?>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-2">

                                </div>
                                <div class="col-10 d-flex flex-wrap justify-content-lg-end gap-1">
                                    <div class="badge bg-secondary text-white"><?= $translator->t('meetings.participants.invited'); ?></div>
                                    <div class="badge bg-success text-white"><?= $translator->t('meetings.participants.present'); ?></div>
                                    <div class="badge bg-warning text-white"><?= $translator->t('meetings.participants.excused'); ?></div>
                                    <div class="badge bg-danger text-white"><?= $translator->t('meetings.participants.absent'); ?></div>
                                </div>
                            </div>
                            <div>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($teilnehmer as $t) { ?>
                                        <li class="list-group-item d-flex align-items-center gap-2 participant-item">
                                            <div class="d-flex align-items-center gap-2 flex-wrap participant-content">
                                                <span class="badge bg-<?= $t->istAnwesend ? 'success' : ($t->istEntschuldigt ? 'warning' : ($t->istAbwesend ? 'danger' : 'secondary')); ?> text-white"></span>

                                                <?php
                                                $funktion = null;

                                                if ($t->funktion !== null) {
                                                    $organisationsFunktionRepository = $app->organisationsFunktionRepository();
                                                    $funktion = $organisationsFunktionRepository->findById((int)$t->funktion);
                                                }
                                                ?>

                                                <div class="d-flex align-items-center gap-2 flex-wrap participant-details">

                                                    <?php

                                                    $chairmanIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-shield">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h2" />
                                                                    <path d="M22 16c0 4 -2.5 6 -3.5 6s-3.5 -2 -3.5 -6c1 0 2.5 -.5 3.5 -1.5c1 1 2.5 1.5 3.5 1.5" />
                                                                    <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                                </svg>';

                                                    $secretaryIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-edit">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                    <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h3.5" />
                                                                    <path d="M18.42 15.61a2.1 2.1 0 0 1 2.97 2.97l-3.39 3.42h-3v-3l3.42 -3.39" />
                                                                </svg>';

                                                    $signIcon = '<svg xmlns="http://www.w3.org/2000/svg"
                                                                            width="18"
                                                                            height="18"
                                                                            viewBox="0 0 24 24"
                                                                            fill="none"
                                                                            stroke="currentColor"
                                                                            stroke-width="2"
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            class="icon icon-tabler icons-tabler-outline icon-tabler-signature text-muted">
                                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                            <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2" />
                                                                        </svg>';

                                                    ?>

                                                    <span><?= htmlspecialchars($t->vorname . ' ' . $t->nachname, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <span class="d-flex gap-2">
                                                        <?php if ((int) $t->istSitzungsleitung === 1) { ?>
                                                            <span title="<?= htmlspecialchars($translator->t('meetings.participants.chairman'), ENT_QUOTES, 'UTF-8'); ?>" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?= htmlspecialchars($translator->t('meetings.participants.chairman'), ENT_QUOTES, 'UTF-8'); ?>"><?= $chairmanIcon; ?></span>
                                                        <?php } ?>
                                                        <?php if ((int) $t->istProtokollfuehrer === 1) { ?>
                                                            <span title="<?= htmlspecialchars($translator->t('meetings.participants.secretary'), ENT_QUOTES, 'UTF-8'); ?>" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?= htmlspecialchars($translator->t('meetings.participants.secretary'), ENT_QUOTES, 'UTF-8'); ?>"><?= $secretaryIcon; ?></span>
                                                        <?php } ?>
                                                        <?php if ((int) $t->darfUnterschreiben === 1) { ?>
                                                            <span title="<?= htmlspecialchars($translator->t('meetings.participants.authToSign'), ENT_QUOTES, 'UTF-8'); ?>" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?= htmlspecialchars($translator->t('meetings.participants.authToSign'), ENT_QUOTES, 'UTF-8'); ?>"><?= $signIcon; ?></span>
                                                        <?php } ?>
                                                    </span>

                                                    <?php if ($funktion !== null): ?>
                                                        <span class="text-muted">
                                                            <?= htmlspecialchars($funktion->bezeichnung, ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                    <?php endif; ?>

                                                </div>

                                                <div class="btn-list ms-auto gap-1 participant-actions">
                                                    <?php
                                                    $canSign = $funktion === null || !$funktion->darfUnterschreiben;
                                                    $isAbsentDisable = ((int) $t->istAbwesend === 1) ? 'disabled' : '';
                                                    ?>

                                                    <a href="./actions.php?action=meetingChairman&meetingId=<?= $besprechung->id; ?>&participantId=<?= $t->id; ?>" class="btn btn-action <?= $isAbsentDisable; ?>" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?= htmlspecialchars($translator->t('meetings.participants.chairman'), ENT_QUOTES, 'UTF-8'); ?>">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                            class="icon icon-tabler icons-tabler-outline icon-tabler-user-shield">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h2" />
                                                            <path d="M22 16c0 4 -2.5 6 -3.5 6s-3.5 -2 -3.5 -6c1 0 2.5 -.5 3.5 -1.5c1 1 2.5 1.5 3.5 1.5" />
                                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                        </svg>
                                                    </a>
                                                    <a href="./actions.php?action=meetingSecretary&meetingId=<?= $besprechung->id; ?>&participantId=<?= $t->id; ?>" class="btn btn-action <?= $isAbsentDisable; ?>" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?= htmlspecialchars($translator->t('meetings.participants.secretary'), ENT_QUOTES, 'UTF-8'); ?>">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-edit">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h3.5" />
                                                            <path d="M18.42 15.61a2.1 2.1 0 0 1 2.97 2.97l-3.39 3.42h-3v-3l3.42 -3.39" />
                                                        </svg>
                                                    </a>
                                                    <a href="./actions.php?action=<?= ((int) $t->darfUnterschreiben === 1) ? 'meetingUnsign' : 'meetingSign'; ?>&meetingId=<?= $besprechung->id; ?>&participantId=<?= $t->id; ?>" class="btn btn-action <?= $isAbsentDisable; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="<?= htmlspecialchars(((int) $t->darfUnterschreiben === 1) ? $translator->t('meetings.participants.notAuthToSign') : $translator->t('meetings.participants.AuthToSign'), ENT_QUOTES, 'UTF-8'); ?>">
                                                        <?php if ($t->darfUnterschreiben === 1) { ?>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-signature-off">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -.394 -.017 -.735 -.05 -1.033m-1.95 -1.967c-1 0 -2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.219 0 .708 -.341 1.231 -.742m3.769 -.258c.303 .245 .64 .677 1 1" />
                                                                <path d="M3 3l18 18" />
                                                            </svg>
                                                        <?php } else { ?>
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                width="18"
                                                                height="18"
                                                                viewBox="0 0 24 24"
                                                                fill="none"
                                                                stroke="currentColor"
                                                                stroke-width="2"
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                class="icon icon-tabler icons-tabler-outline icon-tabler-signature text-muted">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2" />
                                                            </svg>
                                                        <?php } ?>
                                                    </a>
                                                    <div class="vr"></div>
                                                    <a href="./api/getMeetingSingleInvitationAsPDF.php?meetingId=<?= $besprechung->id ?>&participantId=<?= $t->id; ?>" class="btn btn-action" target="_blank" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?= $translator->t('meetings.invitationToPDF'); ?>">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-due">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2l0 -12" />
                                                            <path d="M16 3v4" />
                                                            <path d="M8 3v4" />
                                                            <path d="M4 11h16" />
                                                            <path d="M11 16a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                        </svg>
                                                    </a>
                                                    <div class="vr"></div>
                                                    <button type="button" class="btn btn-action" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?= $translator->t('meetings.participants.present'); ?>" onclick="setParticipantStatus(<?= $besprechung->id; ?>, <?= $t->id; ?>, 'present')"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-check">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
                                                            <path d="M15 19l2 2l4 -4" />
                                                        </svg>
                                                    </button>
                                                    <button type="button" class="btn btn-action" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?= $translator->t('meetings.participants.excused'); ?>" onclick="setParticipantStatus(<?= $besprechung->id; ?>, <?= $t->id; ?>, 'excused')"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-cancel">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h3.5" />
                                                            <path d="M16 19a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                            <path d="M17 21l4 -4" />
                                                        </svg></button>
                                                    <button type="button" class="btn btn-action" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?= $translator->t('meetings.participants.absent'); ?>" onclick="setParticipantStatus(<?= $besprechung->id; ?>, <?= $t->id; ?>, 'absent')"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-down">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4c.342 0 .674 .043 .99 .124" />
                                                            <path d="M19 16v6" />
                                                            <path d="M22 19l-3 3l-3 -3" />
                                                        </svg></button>
                                                </div>
                                            </div>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="hr-text"><?= $translator->translate('meetings.topics') ?></div>

            <div class="row g-3">
                <?php
                $main = 0;
                $child = 0;
                $lastMainPrefix = 0;

                foreach ($tops as $t) {

                    if (empty($t->elternPosition) || $t->elternPosition == 0):

                        $main++;
                        $child = 0;
                        $prefix = $main;
                        $lastMainPrefix = $prefix;

                    else:

                        $child++;
                        $prefix = $lastMainPrefix . '.' . $child;

                    endif;


                ?>
                    <div class="card mb-2" id="topic-<?= $prefix ?>">
                        <div class="card-header">
                            <h3 class="card-title"><b><?= $prefix; ?> • <?= $t->titel; ?></b></h3>
                            <?php if ($authToManageMeetings) { ?>
                                <div class="card-actions">
                                    <button type="button" class="btn btn-icon btn-outline-primary <?php if ($besprechung->status === 'beendet') {
                                                                                                        echo 'disabled';
                                                                                                    } ?>" title="<?= $translator->translate('meetings.editMeetingPoint') ?>" onclick="event.preventDefault(); editMeetingPoint(<?= $t->id ?>)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-edit">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M9 7h-3a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-3" />
                                            <path d="M9 15h3l8.5 -8.5a1.5 1.5 0 0 0 -3 -3l-8.5 8.5v3" />
                                            <line x1="16" y1="5" x2="19" y2="8" />
                                        </svg></button>
                                    <button type="button" class="btn btn-icon btn-outline-danger <?php if ($besprechung->status === 'beendet') {
                                                                                                        echo 'disabled';
                                                                                                    } ?>" title="<?= $translator->translate('meetings.deleteMeetingPoint') ?>" onclick="deleteMeetingPoint(<?= $t->id ?>)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-trash">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <line x1="4" y1="7" x2="20" y2="7" />
                                            <line x1="10" y1="11" x2="10" y2="17" />
                                            <line x1="14" y1="11" x2="14" y2="17" />
                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                        </svg></button>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="card-body">
                            <p><?= $t->beschreibung; ?></p>
                            <?php if ($t->ergebnis) { ?>
                                <div class="alert alert-success d-flex align-items-center" role="alert">
                                    <div>
                                        <?= str_replace("<p>", "<p class='mb-0'>", $t->ergebnis); ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <?php if ($t->typ == 'abstimmung') { ?>
                            <div class="card-footer">
                                <div class="btn-list gap-1 text-center">
                                    <?php
                                    $gesamtStimmen = ($t->ergebnis_zustimmung ?? 0) + ($t->ergebnis_ablehnung ?? 0) + ($t->ergebnis_enthaltung ?? 0);
                                    $jaProzent = $gesamtStimmen > 0 ? round(($t->ergebnis_zustimmung ?? 0) / $gesamtStimmen * 100) : 0;
                                    $neinProzent = $gesamtStimmen > 0 ? round(($t->ergebnis_ablehnung ?? 0) / $gesamtStimmen * 100) : 0;
                                    $ergebnis = $jaProzent > $neinProzent ? 'approved' : ($neinProzent > $jaProzent ? 'rejected' : 'tie');
                                    $ergebnisText = $jaProzent > $neinProzent ? $translator->t('meetings.voting.result.approved') : ($neinProzent > $jaProzent ? $translator->t('meetings.voting.result.rejected') : $translator->t('meetings.voting.result.tie'));
                                    ?>
                                    <button type="button" class="btn btn-outline-success"><?= $t->ergebnis_zustimmung ?? 0 ?> <?= $translator->t('meetings.voting.yes') ?></button>
                                    <button type="button" class="btn btn-outline-danger"><?= $t->ergebnis_ablehnung ?? 0 ?> <?= $translator->t('meetings.voting.no') ?></button>
                                    <button type="button" class="btn btn-outline-secondary me-auto"><?= $t->ergebnis_enthaltung ?? 0 ?> <?= $translator->t('meetings.voting.abstain') ?></button>
                                    <button type="button" class="btn btn-<?= ($ergebnis === 'approved') ? 'success' : (($ergebnis === 'rejected') ? 'danger' : 'secondary') ?>"> <?= $ergebnisText; ?></button>
                                </div>

                            </div>
                        <?php }; ?>

                    </div>
                <?php } ?>
            </div>

            <?php if (!$besprechung->istVeröffentlicht) { ?>
                <div class="hr-text"><?= $translator->translate('meetings.newMeetingPoint') ?></div>
                <div class="text-center">
                    <button class="btn btn-lg btn-success mb-5" data-bs-toggle="modal" data-bs-target="#newMeetingPointModal"><?= $translator->t('meetings.newMeetingPoint') ?></button>
                </div>

            <?php } ?>

            <div class="hr-text"><?= $translator->translate('meetings.tasks.root') ?></div>

            <?php foreach ($tasks as $task) { ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title"><?= htmlspecialchars($task->titel) ?></h3>
                        <div class="card-actions">
                            <a href="#" class="btn btn-icon btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-task-edit"
                                data-task-id="<?= (int) $task->id ?>"
                                data-task-user-id="<?= (int) ($task->verantwortlicherUserId ?? 0) ?>"
                                data-task-title="<?= htmlspecialchars($task->titel, ENT_QUOTES, 'UTF-8') ?>"
                                data-task-description="<?= htmlspecialchars($task->beschreibung ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-task-deadline="<?= htmlspecialchars($task->deadline ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-task-completed="<?= $task->istErledigt ? '1' : '0' ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-edit">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M9 7h-3a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-3" />
                                    <path d="M9 15h3l8.5 -8.5a1.5 1.5 0 0 0 -3 -3l-8.5 8.5v3" />
                                    <line x1="16" y1="5" x2="19" y2="8" />
                                </svg>
                            </a>
                            <a href="#" class="btn btn-icon btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteMeetingTaskModal">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-trash">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <line x1="4" y1="7" x2="20" y2="7" />
                                    <line x1="10" y1="11" x2="10" y2="17" />
                                    <line x1="14" y1="11" x2="14" y2="17" />
                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                </svg>
                            </a>
                            <a href="#" class="btn btn-icon btn-outline-success" data-bs-toggle="modal" data-bs-target="#completeMeetingTaskModal">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-check">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M5 12l5 5l10 -10" />
                                </svg>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?= htmlspecialchars($task->beschreibung) ?></p>
                        <p class="card-text"><small class="text-muted"><?= $translator->t('meetings.tasks.deadline') ?>: <?= $task->deadline ? htmlspecialchars((new DateTime($task->deadline))->format('d.m.Y')) : '' ?></small></p>
                    </div>
                </div>
            <?php } ?>


            <?php if (!$besprechung->istVeröffentlicht) { ?>
                <div class="hr-text"><?= $translator->translate('meetings.tasks.new') ?></div>
                <div class="text-center">
                    <button class="btn btn-lg btn-success mb-5" data-bs-toggle="modal" data-bs-target="#newTaskModal"><?= $translator->t('meetings.tasks.new') ?></button>
                </div>

            <?php } ?>

            <div class="hr-text"><?= $translator->t('meetings.attachments.root') ?></div>

            <div class="card mb-3">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th><?= $translator->t('meetings.attachments.file') ?></th>
                                <th><?= $translator->t('meetings.attachments.description') ?></th>
                                <th><?= $translator->t('meetings.attachments.size') ?></th>
                                <th class="text-center"><?= $translator->t('meetings.attachments.printInvitation') ?></th>
                                <th class="text-center"><?= $translator->t('meetings.attachments.printProtocol') ?></th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($anhaenge)) { ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted"><?= $translator->t('meetings.attachments.empty') ?></td>
                                </tr>
                            <?php } else { ?>
                                <?php foreach ($anhaenge as $anhang) { ?>
                                    <tr>
                                        <td>
                                            <a href="<?= htmlspecialchars((string)$anhang->dateipfad, ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><?= htmlspecialchars($anhang->originalDateiname, ENT_QUOTES, 'UTF-8'); ?></a>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars((string)($anhang->beschreibung ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-muted"><?= $anhang->formatierteGroesse(); ?></td>
                                        <td colspan="2">
                                            <form method="post" action="actions.php?action=updateMeetingAttachmentFlags" class="d-flex justify-content-around align-items-center">
                                                <input type="hidden" name="attachmentId" value="<?= (int)$anhang->id; ?>" />
                                                <input type="hidden" name="meetingId" value="<?= (int)$besprechung->id; ?>" />
                                                <input type="checkbox" class="form-check-input" name="druckenBeiEinladung" value="1" <?= $anhang->druckenBeiEinladung ? 'checked' : ''; ?> onchange="this.form.submit()" data-bs-toggle="tooltip" data-bs-title="<?= $translator->t('meetings.attachments.printInvitation') ?>" />
                                                <input type="checkbox" class="form-check-input" name="druckenBeiProtokoll" value="1" <?= $anhang->druckenBeiProtokoll ? 'checked' : ''; ?> onchange="this.form.submit()" data-bs-toggle="tooltip" data-bs-title="<?= $translator->t('meetings.attachments.printProtocol') ?>" />
                                            </form>
                                        </td>
                                        <td>
                                            <a href="./actions.php?action=deleteMeetingAttachment&attachmentId=<?= (int)$anhang->id; ?>&meetingId=<?= (int)$besprechung->id; ?>" class="btn btn-icon btn-outline-danger" data-bs-toggle="tooltip" data-bs-title="<?= $translator->t('meetings.attachments.delete'); ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <line x1="4" y1="7" x2="20" y2="7" />
                                                    <line x1="10" y1="11" x2="10" y2="17" />
                                                    <line x1="14" y1="11" x2="14" y2="17" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!$besprechung->istVeröffentlicht) { ?>
                <div class="text-center">
                    <button class="btn btn-lg btn-success mb-5" data-bs-toggle="modal" data-bs-target="#uploadAttachmentModal"><?= $translator->t('meetings.attachments.new') ?></button>
                </div>
            <?php } ?>

        </div>

    </div>

    <form action="actions.php?action=uploadMeetingAttachment" method="post" enctype="multipart/form-data" class="modal modal-blur fade" id="uploadAttachmentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $translator->t('meetings.attachments.new'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="meetingId" value="<?= (int)$besprechung->id; ?>" />
                    <div class="mb-3">
                        <label class="form-label"><?= $translator->t('meetings.attachments.file'); ?></label>
                        <input type="file" class="form-control" name="file" accept=".pdf" required />
                        <small class="form-hint"><?= $translator->t('meetings.attachments.allowedTypes'); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $translator->t('meetings.attachments.description'); ?></label>
                        <input type="text" class="form-control" name="beschreibung" maxlength="255" />
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="druckenBeiEinladung" value="1" />
                            <span class="form-check-label"><?= $translator->t('meetings.attachments.printWithInvitation'); ?></span>
                        </label>
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="druckenBeiProtokoll" value="1" />
                            <span class="form-check-label"><?= $translator->t('meetings.attachments.printWithProtocol'); ?></span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary btn-3" data-bs-dismiss="modal"><?= $translator->t('common.cancel'); ?></button>
                    <button type="submit" class="btn btn-primary btn-5 ms-auto"><?= $translator->t('meetings.attachments.upload'); ?></button>
                </div>
            </div>
        </div>
    </form>

    <form action="api/getMeetingAsPDF.php" method="get" target="_blank" class="modal modal-blur fade" id="printToPDF" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $translator->t('meetings.printToPDF'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="meetingId" id="meetingId" value="<?= $besprechung->id; ?>" />
                    <input type="checkbox" class="form-check-input mb-2" name="includeParticipants" id="includeParticipants" value="1" />
                    <label for="includeParticipants"><?= $translator->t('meetings.includeParticipants'); ?></label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary btn-3" data-bs-dismiss="modal"> <?= $translator->t('common.cancel'); ?> </button>
                    <button type="submit" class="btn btn-primary btn-5 ms-auto"><?= $translator->t('meetings.printToPDF'); ?></button>
                </div>
            </div>
        </div>
    </form>

    <form action="./api/getBlankParticipantList.php" method="get" target="_blank" class="modal modal-blur fade" id="blankParticipantList" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $translator->t('meetings.participants.blanklist'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="meetingId" id="meetingId" value="<?= $besprechung->id; ?>" />

                    <div class="input-group mb-2">
                        <input type="number" class="form-control" name="numberOfParticipants" style="text-align: center;" id="numberOfParticipants" min="1" placeholder="<?= $translator->t('meetings.participants.numberOfParticipants'); ?>" value="12" required />
                        <span class="input-group-text"><?= $translator->t('meetings.participants.root'); ?></span>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary btn-3" data-bs-dismiss="modal"> <?= $translator->t('common.cancel'); ?> </button>
                    <button type="submit" class="btn btn-primary btn-5 ms-auto"><?= $translator->t('meetings.participants.blanklist'); ?></button>
                </div>
            </div>
        </div>
    </form>

    <div class="modal modal-blur fade" id="newTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="post" action="actions.php?action=create_task">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $translator->t('meetings.tasks.new'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="besprechung_id" value="<?= $besprechung->id; ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="task-title-create"><?= $translator->t('tasks.title'); ?></label>
                                <input class="form-control" type="text" name="titel" id="task-title-create" maxlength="255" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="task-description-create"><?= $translator->t('tasks.description'); ?></label>
                                <textarea class="form-control" name="beschreibung" id="task-description-create" rows="4"></textarea>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="task-user-create"><?= $translator->t('tasks.assignedTo'); ?></label>
                                <select class="form-select" name="verantwortlicher_user_id" id="task-user-create">
                                    <option value="">-</option>
                                    <?php foreach ($alleBenutzer as $benutzer): ?>
                                        <option value="<?= (int) $benutzer->id ?>"><?= htmlspecialchars(trim($benutzer->vorname . ' ' . $benutzer->nachname), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="task-deadline-create"><?= $translator->t('tasks.dueDate'); ?></label>
                                <input class="form-control" type="date" name="deadline" id="task-deadline-create">
                            </div>
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

    <form action="actions.php?action=editTask" method="post" class="modal modal-blur fade" id="editTaskModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $translator->t('meetings.tasks.edit'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="editTaskId" id="editTaskId" value="" />

                    <div class="mb-3">
                        <label class="form-label"><?= $translator->t('meetings.tasks.description'); ?></label>
                        <textarea class="form-control" name="editTaskDescription" id="editTaskDescription" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $translator->t('meetings.tasks.deadline'); ?></label>
                        <input type="date" class="form-control" name="editTaskDeadline" id="editTaskDeadline" />
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary btn-3" data-bs-dismiss="modal"> <?= $translator->t('common.cancel'); ?> </button>
                    <button type="submit" class="btn btn-primary btn-5 ms-auto"><?= $translator->t('meetings.tasks.edit'); ?></button>
                </div>
            </div>
        </div>
    </form>

    <form action="actions.php?action=deleteTask" method="post" class="modal modal-blur fade" id="deleteTaskModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $translator->t('meetings.tasks.delete'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="deleteTaskId" id="deleteTaskId" value="" />
                    <p><?= $translator->t('meetings.tasks.deleteConfirmation'); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary btn-3" data-bs-dismiss="modal"> <?= $translator->t('common.cancel'); ?> </button>
                    <button type="submit" class="btn btn-danger btn-5 ms-auto"><?= $translator->t('meetings.tasks.delete'); ?></button>
                </div>
            </div>
        </div>
    </form>

    <div class="modal modal-blur fade" id="modal-task-edit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="post" action="actions.php?action=update_task">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $translator->t('tasks.edit'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="task_id" id="task-id-edit">
                        <input type="hidden" name="besprechung_id" value="<?= $besprechung->id; ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="task-title-edit"><?= $translator->t('tasks.title'); ?></label>
                                <input class="form-control" type="text" name="titel" id="task-title-edit" maxlength="255" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="task-description-edit"><?= $translator->t('tasks.description'); ?></label>
                                <textarea class="form-control" name="beschreibung" id="task-description-edit" rows="4"></textarea>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="task-user-edit"><?= $translator->t('tasks.assignedTo'); ?></label>
                                <select class="form-select" name="verantwortlicher_user_id" id="task-user-edit">
                                    <option value="">-</option>
                                    <?php foreach ($alleBenutzer as $benutzer): ?>
                                        <option value="<?= (int) $benutzer->id ?>"><?= htmlspecialchars(trim($benutzer->vorname . ' ' . $benutzer->nachname), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="task-deadline-edit"><?= $translator->t('tasks.dueDate'); ?></label>
                                <input class="form-control" type="date" name="deadline" id="task-deadline-edit">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="task-completed-edit"><?= $translator->t('tasks.status'); ?></label>
                                <select class="form-select" name="ist_erledigt" id="task-completed-edit">
                                    <option value="0"><?= $translator->t('meetings.tasks.incomplete'); ?></option>
                                    <option value="1"><?= $translator->t('meetings.tasks.complete'); ?></option>
                                </select>
                            </div>
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

    <?php if ($authToManageMeetings) { ?>

        <form action="actions.php?action=editMeeting" method="post" class="modal modal-blur fade" id="editMeeting" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $translator->t('meetings.edit'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="editMeetingId" id="editMeetingId" value="<?= $besprechung->id; ?>" />
                        <div class="mb-3">
                            <label class="form-label"><?= $translator->t('meetings.title'); ?></label>
                            <input type="text" class="form-control" name="editMeetingTitle" id="editMeetingTitle" placeholder="Titel" value="<?= $besprechung->titel; ?>" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= $translator->t('meetings.location'); ?></label>
                            <input type="text" class="form-control" name="editMeetingLocation" id="editMeetingLocation" placeholder="Ort" value="<?= $besprechung->ort; ?>" />
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?= $translator->t('meetings.date'); ?></label>
                                <input type="date" class="form-control" name="editMeetingDate" id="editMeetingDate" value="<?= $besprechung->datum; ?>" />
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label"><?= $translator->t('meetings.start'); ?></label>
                                <input type="time" class="form-control" name="editMeetingBegin" id="editMeetingBegin" value="<?= $besprechung->beginn; ?>" />
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label"><?= $translator->t('meetings.end'); ?></label>
                                <input type="time" class="form-control" name="editMeetingEnde" id="editMeetingEnde" value="<?= $besprechung->ende; ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary btn-3" data-bs-dismiss="modal"> <?= $translator->t('common.cancel'); ?> </button>
                        <button type="submit" class="btn btn-primary btn-5 ms-auto">
                            <!-- Download SVG icon from http://tabler.io/icons/icon/plus -->
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon icon-2">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg>
                            <?= $translator->t('common.save'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <form action="actions.php?action=deleteMeeting" method="post" class="modal modal-blur fade" id="deleteMeeting" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered modal-dialog-danger" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $translator->t('meetings.delete'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="deleteMeetingId" id="deleteMeetingId" value="<?= $besprechung->id; ?>" />
                        <div class="mb-3">
                            <label class="form-label">
                                <?= $translator->t('text.deleteMeeting'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary btn-3" data-bs-dismiss="modal"> <?= $translator->t('cancel'); ?> </button>
                        <button type="submit" class="btn btn-danger btn-5 ms-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-minus">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 12l14 0" />
                            </svg>
                            <?= $translator->t('meetings.delete'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <form action="actions.php?action=editMeetingPoint" method="post" class="modal modal-blur fade" id="editMeetingPointTextModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $translator->t('meetings.editMeetingPoint'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="editMeetingPointId" id="editMeetingPointId" value="" />
                        <div class="mb-3">
                            <label class="form-label"><b><?= $translator->t('meetings.title'); ?></b></label>
                            <input type="text" class="form-control" name="editMeetingPointTitle" id="editMeetingPointTitle" placeholder="Titel" value="" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><b><?= $translator->t('meetings.type'); ?></b></label>
                            <div class="form-selectgroup">
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="editMeetingPointType" value="information" class="form-selectgroup-input">
                                    <span class="form-selectgroup-label"><!-- Download SVG icon from http://tabler.io/icons/icon/home -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-info-circle">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                            <path d="M12 9h.01" />
                                            <path d="M11 12h1v4h1" />
                                        </svg>
                                        <?= $translator->t('meetings.types.information'); ?></span>
                                </label>
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="editMeetingPointType" value="diskussion" class="form-selectgroup-input">
                                    <span class="form-selectgroup-label"><!-- Download SVG icon from http://tabler.io/icons/icon/user -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-users-group">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                            <path d="M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1" />
                                            <path d="M15 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                            <path d="M17 10h2a2 2 0 0 1 2 2v1" />
                                            <path d="M5 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                            <path d="M3 13v-1a2 2 0 0 1 2 -2h2" />
                                        </svg>
                                        <?= $translator->t('meetings.types.discussion'); ?></span>
                                </label>
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="editMeetingPointType" value="abstimmung" class="form-selectgroup-input">
                                    <span class="form-selectgroup-label"><!-- Download SVG icon from http://tabler.io/icons/icon/circle -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-circle-dashed-check">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M8.56 3.69a9 9 0 0 0 -2.92 1.95" />
                                            <path d="M3.69 8.56a9 9 0 0 0 -.69 3.44" />
                                            <path d="M3.69 15.44a9 9 0 0 0 1.95 2.92" />
                                            <path d="M8.56 20.31a9 9 0 0 0 3.44 .69" />
                                            <path d="M15.44 20.31a9 9 0 0 0 2.92 -1.95" />
                                            <path d="M20.31 15.44a9 9 0 0 0 .69 -3.44" />
                                            <path d="M20.31 8.56a9 9 0 0 0 -1.95 -2.92" />
                                            <path d="M15.44 3.69a9 9 0 0 0 -3.44 -.69" />
                                            <path d="M9 12l2 2l4 -4" />
                                        </svg>
                                        <?= $translator->t('meetings.types.voting'); ?></span>
                                </label>

                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><b><?= $translator->t('meetings.description'); ?></b></label>
                            <textarea class="form-control" name="editMeetingPointDescription" id="editMeetingPointDescription" placeholder="Beschreibung" rows="6"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><b><?= $translator->t('meetings.parentTopic'); ?></b></label>
                            <select class="form-select" name="editMeetingPointParent" id="editMeetingPointParent">
                                <option value="0"><?= $translator->t('meetings.noParentTopic'); ?></option>
                                <?php foreach ($tops as $t) { ?>
                                    <option value="<?= $t->id; ?>"><?= $t->titel; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3" name="vote" id="editVoteFields">
                            <label class="form-label"><b><?= $translator->t('meetings.voting'); ?></b></label>
                            <div class="row gx-2">
                                <div class="col">
                                    <label class="form-label"><?= $translator->t('meetings.voting.yes'); ?></label>
                                    <input type="number" class="form-control text-center" name="editMeetingPointVotingYes" id="editMeetingPointVotingYes" placeholder="<?= $translator->t('meetings.voting.yes'); ?>" value="0" />
                                </div>
                                <div class="col">
                                    <label class="form-label"><?= $translator->t('meetings.voting.no'); ?></label>
                                    <input type="number" class="form-control text-center" name="editMeetingPointVotingNo" id="editMeetingPointVotingNo" placeholder="<?= $translator->t('meetings.voting.no'); ?>" value="0" />
                                </div>
                                <div class="col">
                                    <label class="form-label"><?= $translator->t('meetings.voting.abstain'); ?></label>
                                    <input type="number" class="form-control text-center" name="editMeetingPointVotingAbstain" id="editMeetingPointVotingAbstain" placeholder="<?= $translator->t('meetings.voting.abstain'); ?>" value="0" />
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><b><?= $translator->t('meetings.result'); ?></b></label>
                            <textarea class="form-control" name="editMeetingPointResult" id="editMeetingPointResult" placeholder="Ergebnis" rows="6"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary btn-3" data-bs-dismiss="modal"> <?= $translator->t('common.cancel'); ?> </button>
                        <button type="submit" class="btn btn-primary btn-5 ms-auto">
                            <!-- Download SVG icon from http://tabler.io/icons/icon/plus -->
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon icon-2">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg>
                            <?= $translator->t('common.save'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Meetingpunkt hinzufügen -->
        <form action="actions.php?action=addMeetingPoint" method="post" class="modal modal-blur fade" id="newMeetingPointModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $translator->t('meetings.newMeetingPoint'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="newMeetingPointId" id="newMeetingPointId" value="<?= $besprechung->id; ?>" />
                        <div class="mb-3">
                            <label class="form-label"><b><?= $translator->t('meetings.title'); ?></b></label>
                            <input type="text" class="form-control" name="newMeetingPointTitle" id="newMeetingPointTitle" placeholder="<?= $translator->t('meetings.title'); ?>" value="" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><b><?= $translator->t('meetings.type'); ?></b></label>
                            <div class="form-selectgroup">
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="newMeetingPointType" value="information" class="form-selectgroup-input">
                                    <span class="form-selectgroup-label"><!-- Download SVG icon from http://tabler.io/icons/icon/home -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-info-circle">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                            <path d="M12 9h.01" />
                                            <path d="M11 12h1v4h1" />
                                        </svg>
                                        <?= $translator->t('meetings.types.information'); ?></span>
                                </label>
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="newMeetingPointType" value="diskussion" class="form-selectgroup-input">
                                    <span class="form-selectgroup-label"><!-- Download SVG icon from http://tabler.io/icons/icon/user -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-users-group">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                            <path d="M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1" />
                                            <path d="M15 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                            <path d="M17 10h2a2 2 0 0 1 2 2v 1" />
                                            <path d="M5 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                            <path d="M3 13v-1a2 2 0 0 1 2 -2h2" />
                                        </svg>
                                        <?= $translator->t('meetings.types.discussion'); ?></span>
                                </label>
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="newMeetingPointType" value="abstimmung" class="form-selectgroup-input">
                                    <span class="form-selectgroup-label"><!-- Download SVG icon from http://tabler.io/icons/icon/circle -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-circle-dashed-check">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M8.56 3.69a9 9 0 0 0 -2.92 1.95" />
                                            <path d="M3.69 8.56a9 9 0 0 0 -.69 3.44" />
                                            <path d="M3.69 15.44a9 9 0 0 0 1.95 2.92" />
                                            <path d="M8.56 20.31a9 9 0 0 0 3.44 .69" />
                                            <path d="M15.44 20.31a9 9 0 0 0 2.92 -1.95" />
                                            <path d="M20.31 15.44a9 9 0 0 0 .69 -3.44" />
                                            <path d="M20.31 8.56a9 9 0 0 0 -1.95 -2.92" />
                                            <path d="M15.44 3.69a9 9 0 0 0 -3.44 -.69" />
                                            <path d="M9 12l2 2l4 -4" />
                                        </svg>
                                        <?= $translator->t('meetings.types.voting'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><b><?= $translator->t('meetings.description'); ?></b></label>
                            <textarea class="form-control" name="newMeetingPointDescription" id="newMeetingPointDescription" placeholder="<?= $translator->t('meetings.description'); ?>" rows="6"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><b><?= $translator->t('meetings.parentTopic'); ?></b></label>
                            <select class="form-select" name="newMeetingPointParent" id="newMeetingPointParent">
                                <option value="0"><?= $translator->t('meetings.noParentTopic'); ?></option>
                                <?php foreach ($tops as $t) { ?>
                                    <option value="<?= $t->id; ?>"><?= $t->titel; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3" name="vote" id="newVoteFields">
                            <label class="form-label"><b><?= $translator->t('meetings.voting.root'); ?></b></label>
                            <div class="row gx-2">
                                <div class="col">
                                    <label class="form-label"><?= $translator->t('meetings.voting.yes'); ?></label>
                                    <input type="number" class="form-control text-center" name="newMeetingPointVotingYes" id="newMeetingPointVotingYes" placeholder="<?= $translator->t('meetings.voting.yes'); ?>" value="0" />
                                </div>
                                <div class="col">
                                    <label class="form-label"><?= $translator->t('meetings.voting.no'); ?></label>
                                    <input type="number" class="form-control text-center" name="newMeetingPointVotingNo" id="newMeetingPointVotingNo" placeholder="<?= $translator->t('meetings.voting.no'); ?>" value="0" />
                                </div>
                                <div class="col">
                                    <label class="form-label"><?= $translator->t('meetings.voting.abstain'); ?></label>
                                    <input type="number" class="form-control text-center" name="newMeetingPointVotingAbstain" id="newMeetingPointVotingAbstain" placeholder="<?= $translator->t('meetings.voting.abstain'); ?>" value="0" />
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><b><?= $translator->t('meetings.result'); ?></b></label>
                            <textarea class="form-control" name="newMeetingPointResult" id="newMeetingPointResult" placeholder="<?= $translator->t('meetings.result'); ?>" rows="6"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary btn-3" data-bs-dismiss="modal"> <?= $translator->t('common.cancel'); ?> </button>
                        <button type="submit" class="btn btn-primary btn-5 ms-auto">
                            <!-- Download SVG icon from http://tabler.io/icons/icon/plus -->
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon icon-2">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg>
                            <?= $translator->t('common.save'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <form action="actions.php?action=publishMeeting" method="post" class="modal modal-blur fade" id="publishMeetingModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $translator->t('meetings.publish'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="publishMeetingId" id="publishMeetingId" value="<?= $besprechung->id; ?>" />
                        <div class="mb-3">
                            <label class="form-label"><b><?= $translator->t('text.meeting.publish'); ?></b></label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary btn-3" data-bs-dismiss="modal"> <?= $translator->t('cancel'); ?> </button>
                        <button type="submit" class="btn btn-primary btn-5 ms-auto">
                            <!-- Download SVG icon from http://tabler.io/icons/icon/plus -->
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon icon-2">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg>
                            <?= $translator->t('meetings.publish'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <form action="actions.php?action=unpublishMeeting" method="post" class="modal modal-blur fade" id="unpublishMeetingModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $translator->t('meetings.unpublish'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="unpublishMeetingId" id="unpublishMeetingId" value="<?= $besprechung->id; ?>" />
                        <div class="mb-3">
                            <label class="form-label"><b><?= $translator->t('text.meeting.unpublish'); ?></b></label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary btn-3" data-bs-dismiss="modal"> <?= $translator->t('cancel'); ?> </button>
                        <button type="submit" class="btn btn-primary btn-5 ms-auto">
                            <!-- Download SVG icon from http://tabler.io/icons/icon/plus -->
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon icon-2">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg>
                            <?= $translator->t('meetings.unpublish'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    <?php } ?>

    <form action="actions.php?action=sendInvitations" method="post" class="modal modal-blur fade" id="sendInvitation" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $translator->t('meetings.participants.sendInvitationModalTitle'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="sendInvitationMeetingId" value="<?= $besprechung->id; ?>" />

                    <div class="mb-2">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" id="sendInvitationSelectAll">
                            <span class="form-check-label"><?= $translator->t('meetings.participants.selectAll'); ?></span>
                        </label>
                    </div>

                    <p class="text-muted"><?= $translator->t('meetings.participants.selectRecipients'); ?></p>

                    <ul class="list-group list-group-flush">
                        <?php foreach ($teilnehmer as $t) { ?>
                            <?php $hasMail = !empty($t->mail); ?>
                            <li class="list-group-item">
                                <label class="form-check">
                                    <input type="checkbox"
                                        class="form-check-input sendInvitationParticipant"
                                        name="sendInvitationParticipantIds[]"
                                        value="<?= $t->id; ?>">
                                    <span class="form-check-label">
                                        <?= htmlspecialchars($t->vorname . ' ' . $t->nachname, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if ($hasMail) { ?>
                                            <span class="text-muted">(<?= htmlspecialchars($t->mail, ENT_QUOTES, 'UTF-8'); ?>)</span>
                                        <?php } else { ?>
                                            <span class="text-danger"><?= $translator->t('meetings.participants.noEmail'); ?></span>
                                        <?php } ?>
                                    </span>
                                </label>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary btn-3" data-bs-dismiss="modal"> <?= $translator->t('common.cancel'); ?> </button>
                    <button type="submit" class="btn btn-primary btn-5 ms-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-2">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 6l3 -1l3 1l3 -1l3 1l3 -1l3 1v13l-3 -1l-3 1l-3 -1l-3 1l-3 -1l-3 1z" />
                            <line x1="9" y1="7" x2="15" y2="7" />
                            <line x1="9" y1="11" x2="15" y2="11" />
                        </svg>
                        <?= $translator->t('meetings.participants.sentInvitation'); ?>
                    </button>
                </div>
            </div>
        </div>
    </form>

    <form action="actions.php?action=addMeetingParticipants" method="post" class="modal modal-blur fade" id="addParticipantModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $translator->t('meetings.participants.add'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="meetingId" value="<?= $besprechung->id; ?>" />

                    <label class="form-label"><?= $translator->t('meetings.participants.root'); ?></label>
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <label class="form-label text-secondary"><?= $translator->t('meetings.participants.available'); ?></label>
                            <select id="teilnehmer-verfuegbar" multiple size="10" class="form-select">
                                <?php foreach ($verfuegbarePersonen as $person): ?>
                                    <option value="<?= $person->id; ?>"><?= htmlspecialchars($person->vorname . ' ' . $person->nachname, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-auto d-flex flex-column gap-2">
                            <button type="button" id="btn-participant-add" class="btn btn-icon" title="<?= $translator->t('meetings.participants.addSelected'); ?>">&gt;</button>
                            <button type="button" id="btn-participant-add-all" class="btn btn-icon" title="<?= $translator->t('meetings.participants.addAll'); ?>">&gt;&gt;</button>
                            <button type="button" id="btn-participant-remove-all" class="btn btn-icon" title="<?= $translator->t('meetings.participants.removeAll'); ?>">&lt;&lt;</button>
                            <button type="button" id="btn-participant-remove" class="btn btn-icon" title="<?= $translator->t('meetings.participants.removeSelected'); ?>">&lt;</button>
                        </div>

                        <div class="col">
                            <label class="form-label text-secondary"><?= $translator->t('meetings.participants.selected'); ?></label>
                            <select id="teilnehmer-ausgewaehlt" name="teilnehmer[]" multiple size="10" class="form-select"></select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary btn-3" data-bs-dismiss="modal"> <?= $translator->t('common.cancel'); ?> </button>
                    <button type="submit" class="btn btn-primary btn-5 ms-auto"><?= $translator->t('meetings.participants.add'); ?></button>
                </div>
            </div>
        </div>
    </form>

    <?php include_once 'footer.php'; ?>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/hugerte@1.0.10/hugerte.min.js"></script>

<!-- HugeRTE Initialization for Description -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let options = {
            selector: "#editMeetingPointDescription, #editMeetingPointResult, #newMeetingPointDescription, #newMeetingPointResult",
            height: 300,
            menubar: false,
            statusbar: false,
            plugins: [
                "advlist", "autolink", "lists", "link", "image", "charmap",
                "preview", "anchor", "searchreplace", "visualblocks", "code",
                "fullscreen", "insertdatetime", "media", "table", "help", "wordcount"
            ],
            toolbar: "undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat",
            content_style: "body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }"
        };

        if (localStorage.getItem("tablerTheme") === "dark") {
            options.skin = "oxide-dark";
            options.content_css = "dark";
        }

        hugeRTE.init(options);
    });
</script>

<script>
    function editMeetingPoint(id) {
        fetch(`api/getMeetingPoint.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('editMeetingPointId').value = data.id;
                document.getElementById('editMeetingPointTitle').value = data.titel;
                hugeRTE.get("editMeetingPointDescription").setContent(data.beschreibung);
                document.getElementById('editMeetingPointParent').value = data.elternPosition ?? 0;

                const typeValue = ['information', 'diskussion', 'abstimmung'].includes(data.typ) ? data.typ : 'information';
                document.querySelectorAll('input[name="editMeetingPointType"]').forEach(input => {
                    input.checked = input.value === typeValue;
                });

                if (typeValue !== 'abstimmung') {
                    document.getElementById('editVoteFields').style.display = 'none';
                    document.getElementById('editMeetingPointVotingYes').value = 0;
                    document.getElementById('editMeetingPointVotingNo').value = 0;
                    document.getElementById('editMeetingPointVotingAbstain').value = 0;
                } else {
                    document.getElementById('editVoteFields').style.display = 'block';
                    document.getElementById('editMeetingPointVotingYes').value = data.ergebnis_zustimmung ?? 0;
                    document.getElementById('editMeetingPointVotingNo').value = data.ergebnis_ablehnung ?? 0;
                    document.getElementById('editMeetingPointVotingAbstain').value = data.ergebnis_enthaltung ?? 0;
                }

                hugeRTE.get("editMeetingPointResult").setContent(data.ergebnis);

                var editModal = new bootstrap.Modal(document.getElementById('editMeetingPointTextModal'));
                editModal.show();
            });

    }
</script>

<script>
    async function setParticipantStatus(besprechungId, participantId, status) {

            console.log(`Setting status for participant ${participantId} to ${status} in meeting ${besprechungId}`);

            try {
                const response = await fetch('api/setMeetingParticipantStatus.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        besprechungId,
                        participantId,
                        status
                    })
                });

                const text = await response.text();
                let data;

                try {
                    data = JSON.parse(text);
                } catch (err) {
                    console.error('Invalid JSON response from API:', text);
                    alert('Server returned an invalid response. See console for details.');
                    return;
                }

                if (!response.ok) {
                    console.error('API response error:', response.status, data);
                    alert(data.error || 'Server error while updating participant status.');
                    return;
                }

                if (data.error) {
                    alert(data.error);
                    return;
                }

                location.reload();
            } catch (error) {
                console.error('Fetch error:', error);
                alert('An error occurred while updating participant status.');
            }
        }
</script>

<script>
        document.addEventListener('DOMContentLoaded', function() {
            const verfuegbar = document.getElementById('teilnehmer-verfuegbar');
            const ausgewaehlt = document.getElementById('teilnehmer-ausgewaehlt');

            if (!verfuegbar || !ausgewaehlt) return;

            const form = ausgewaehlt.closest('form');

            function moveOptions(from, to, onlySelected) {
                Array.from(from.options).forEach(function(option) {
                    if (!onlySelected || option.selected) {
                        option.selected = false;
                        to.appendChild(option);
                    }
                });
            }

            document.getElementById('btn-participant-add').addEventListener('click', function() {
                moveOptions(verfuegbar, ausgewaehlt, true);
            });

            document.getElementById('btn-participant-remove').addEventListener('click', function() {
                moveOptions(ausgewaehlt, verfuegbar, true);
            });

            document.getElementById('btn-participant-add-all').addEventListener('click', function() {
                moveOptions(verfuegbar, ausgewaehlt, false);
            });

            document.getElementById('btn-participant-remove-all').addEventListener('click', function() {
                moveOptions(ausgewaehlt, verfuegbar, false);
            });

            form.addEventListener('submit', function() {
                Array.from(ausgewaehlt.options).forEach(function(option) {
                    option.selected = true;
                });
            });
        });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('modal-task-edit');

        if (editModal) {
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (!button) return;

                document.getElementById('task-id-edit').value = button.getAttribute('data-task-id') || '';
                document.getElementById('task-user-edit').value = button.getAttribute('data-task-user-id') || '';
                document.getElementById('task-title-edit').value = button.getAttribute('data-task-title') || '';
                document.getElementById('task-description-edit').value = button.getAttribute('data-task-description') || '';
                document.getElementById('task-deadline-edit').value = button.getAttribute('data-task-deadline') || '';
                document.getElementById('task-completed-edit').value = button.getAttribute('data-task-completed') || '0';
            });
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('sendInvitationSelectAll');
        const checkboxes = document.querySelectorAll('.sendInvitationParticipant:not([disabled])');

        if (!selectAll) return;

        selectAll.checked = Array.from(checkboxes).every(cb => cb.checked);

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });

        checkboxes.forEach(cb => cb.addEventListener('change', function() {
            selectAll.checked = Array.from(checkboxes).every(c => c.checked);
        }));
    });
</script>


</html>