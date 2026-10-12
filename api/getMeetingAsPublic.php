<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../src/init.php';

/*/ Check ob man auf die Seite zugreifen darf
$togglePublicMeeting = ($config->get('general', 'togglePublicMeeting') ?? 0) === 1;

if (!$togglePublicMeeting) {
    $redirect->to('../index.php');
}*/

$meeting = $meetingRepository->findByPublicCode($_GET['meeting'] ?? '');

if (!$meeting) {
    $flash->set('errorMsg', 'Besprechung nicht gefunden');
    $flash->set('errorColor', 'red');
    $flash->set('errorCode', '404');
    $redirect->to('../error.php');
}

$datum = (new DateTime($meeting->date))->format('d.m.Y');
$zeiten = (new DateTime($meeting->startTime))->format('H:i') . ' - ' . (new DateTime($meeting->endTime))->format('H:i');
$dauer = (new DateTime($meeting->endTime))->getTimestamp() - (new DateTime($meeting->startTime))->getTimestamp();
$dauer = round($dauer / 60);

$tops = $meetingPointRepository->findByMeetingId($meeting->id);

$teilnehmer = $meetingParticipantRepository->findByMeetingId($meeting->id);
$anwesendeTeilnehmer = array_filter($teilnehmer, function ($t) {
    return $t->isPresent;
});


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeetLog</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/hugerte@1.0.10/skins/ui/oxide/content.min.css">

</head>

<body>

    <div class="page">
        <div class="sticky-top mb-2">
            <header class="navbar navbar-expand-md sticky-top d-print-none">
                <div class="container-xl justify-content-center">
                    <div class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                        <a href="." aria-label="Tabler"><img src="../img/meetlog_256.png" width="75%"></a>
                    </div>
                    <div class="navbar-nav flex-row order-md-last">
                        <div class="nav-item d-none d-md-flex me-3">
                            <a href="../login.php" class="btn btn-outline-primary">
                                <?= $translator->t('common.login'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </header>
        </div>

        <div class="container-xl">

            <div class="row g-3">
                <div class="col-8">

                    <div class="card mb-2">
                        <div class="card-header">
                            <h2 class="card-title" style="font-weight: bold;"><?= $meeting->title; ?></h2>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                                <li class="nav-item">
                                    <a href="#tabs-toc" class="nav-link active" data-bs-toggle="tab">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="icon me-2"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            stroke-width="2"
                                            stroke="currentColor"
                                            fill="none"
                                            stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <polyline points="5 12 3 12 12 3 21 12 19 12" />
                                            <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                                            <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                                        </svg>
                                        <?= $translator->t('meetings.toc'); ?>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#tabs-participants" class="nav-link" data-bs-toggle="tab">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="icon me-2"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            stroke-width="2"
                                            stroke="currentColor"
                                            fill="none"
                                            stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <circle cx="12" cy="7" r="4" />
                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                        </svg>
                                        <?= $translator->t('meetings.participants.root'); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="tabs-toc">
                                    <div>
                                        <ul class="list-group list-group-flush">
                                            <?php
                                            $i = 0;
                                            ?>
                                            <?php foreach ($tops as $t) {
                                                $position = isset($t->position) ? $t->position : ++$i;
                                                $prefix = ($t->parentPosition ?? 0) != 0 ? $t->parentPosition . '.' . $position : $position;
                                            ?>
                                                <li class="list-group-item">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div>
                                                            <a href="#topic-<?= $prefix ?>" class="text-decoration-none">
                                                                <h4 class="mb-0"><?= $prefix; ?> • <?= $t->title; ?></h4>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>
                                <div class="tab-pane" id="tabs-participants">
                                    <div class="row">
                                        <div class="col-7">
                                        </div>
                                        <div class="col-5 justify-content-end mb-2">
                                            <div class="badge bg-secondary text-white"><?= $translator->t('meetings.participants.invited'); ?></div>
                                            <div class="badge bg-success text-white"><?= $translator->t('meetings.participants.present'); ?></div>
                                            <div class="badge bg-warning text-white"><?= $translator->t('meetings.participants.excused'); ?></div>
                                            <div class="badge bg-danger text-white"><?= $translator->t('meetings.participants.absent'); ?></div>
                                        </div>
                                    </div>
                                    <div>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($teilnehmer as $t) { ?>
                                                <li class="list-group-item d-flex align-items-center gap-1">
                                                    <span class="badge bg-<?= $t->isPresent ? 'success' : ($t->isExcused ? 'warning' : ($t->isAbsent ? 'danger' : 'secondary')); ?> text-white"></span>
                                                    <div>
                                                        <?= $t->firstName . ' ' . $t->lastName; ?>
                                                        <div class="text-muted"><?= $t->function; ?></div>
                                                    </div>
                                                    <div class="btn-list ms-auto gap-1">

                                                    </div>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-4">

                    <div class="card mb-2">
                        <div class="card-header">
                            <h3 class="card-title"><?= $translator->t('meetings.basics'); ?></h3>
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
                                    if ($meeting->status === 'planned') {
                                        echo '<span class="badge bg-secondary text-white">' . $translator->t('meetings.status.planned') . '</span>';
                                    } elseif ($meeting->status === 'active') {
                                        echo '<span class="badge bg-success text-white">' . $translator->t('meetings.status.active') . '</span>';
                                    } elseif ($meeting->status === 'finished') {
                                        echo '<span class="badge bg-dark text-white">' . $translator->t('meetings.status.finished') . '</span>';
                                    }
                                    ?>
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
                                    <?= $meeting->location; ?>
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
                                    <?= $meeting->createdAt; ?>
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
                                    <?= $userRepository->findById($meeting->creatorUserId)->firstName . ' ' . $userRepository->findById($meeting->creatorUserId)->lastName; ?>
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
                                    <?= $meeting->updatedAt; ?>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>
            </div>

            <div class="hr-text"><?= $translator->translate('meetings.topics') ?></div>

            <div class="row g-3">
                <?php foreach ($tops as $t) {
                    $position = isset($t->position) ? $t->position : ++$i;
                    $prefix = ($t->parentPosition ?? 0) != 0 ? $t->parentPosition . '.' . $position : $position;
                ?>
                    <div class="card mb-2" id="topic-<?= $prefix ?>">
                        <div class="card-header">
                            <h3 class="card-title"><b><?= $prefix; ?> • <?= $t->title; ?></h3></b>
                        </div>
                        <div class="card-body">
                            <p><?= $t->description; ?></p>
                            <?php if ($t->result) { ?>
                                <p><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-sum">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M18 16v2a1 1 0 0 1 -1 1h-11l6 -7l-6 -7h11a1 1 0 0 1 1 1v2" />
                                    </svg> <?= $t->result; ?></p>
                            <?php } ?>
                        </div>
                        <?php if ($t->type == 'voting') { ?>
                            <div class="card-footer">
                                <div class="btn-list gap-1 text-center">
                                    <?php
                                    $gesamtStimmen = ($t->resultApproval ?? 0) + ($t->resultRejection ?? 0) + ($t->resultAbstention ?? 0);
                                    $jaProzent = $gesamtStimmen > 0 ? round(($t->resultApproval ?? 0) / $gesamtStimmen * 100) : 0;
                                    $neinProzent = $gesamtStimmen > 0 ? round(($t->resultRejection ?? 0) / $gesamtStimmen * 100) : 0;
                                    $ergebnis = $jaProzent > $neinProzent ? 'approved' : ($neinProzent > $jaProzent ? 'rejected' : 'tie');
                                    $ergebnisText = $jaProzent > $neinProzent ? $translator->t('meetings.voting.result.approved') : ($neinProzent > $jaProzent ? $translator->t('meetings.voting.result.rejected') : $translator->t('meetings.voting.result.tie'));
                                    ?>
                                    <button type="button" class="btn btn-outline-success"><?= $t->resultApproval ?? 0 ?> <?= $translator->t('meetings.voting.yes') ?></button>
                                    <button type="button" class="btn btn-outline-danger"><?= $t->resultRejection ?? 0 ?> <?= $translator->t('meetings.voting.no') ?></button>
                                    <button type="button" class="btn btn-outline-secondary me-auto"><?= $t->resultAbstention ?? 0 ?> <?= $translator->t('meetings.voting.abstain') ?></button>
                                    <button type="button" class="btn btn-<?= ($ergebnis === 'approved') ? 'success' : (($ergebnis === 'rejected') ? 'danger' : 'secondary') ?>"> <?= $ergebnisText; ?></button>
                                </div>

                            </div>
                        <?php }; ?>

                    </div>
                <?php } ?>
            </div>

            <?php if (!$meeting->isPublished) { ?>
                <div class="hr-text"><?= $translator->translate('meetings.newtopic') ?></div>
                <div class="text-center">
                    <button class="btn btn-lg btn-success mb-5"><?= $translator->t('meetings.newtopic') ?></button>
                </div>

            <?php } ?>

        </div>

    </div>



</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

</html>