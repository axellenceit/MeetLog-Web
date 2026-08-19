<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../src/init.php';

/*/ Check ob man auf die Seite zugreifen darf
$togglePublicMeeting = ($config->get('general', 'togglePublicMeeting') ?? 0) === 1;

if (!$togglePublicMeeting) {
    $redirect->to('../index.php');
}*/

$besprechung = $besprechungRepository->findByPublicCode($_GET['besprechung'] ?? '');

if (!$besprechung) {
    $flash->set('errorMsg', 'Besprechung nicht gefunden');
    $flash->set('errorColor', 'red');
    $flash->set('errorCode', '404');
    $redirect->to('../error.php');
}

$datum = (new DateTime($besprechung->datum))->format('d.m.Y');
$zeiten = (new DateTime($besprechung->beginn))->format('H:i') . ' - ' . (new DateTime($besprechung->ende))->format('H:i');
$dauer = (new DateTime($besprechung->ende))->getTimestamp() - (new DateTime($besprechung->beginn))->getTimestamp();
$dauer = round($dauer / 60);

$tops = $besprechungspunktRepository->findByBesprechungId($besprechung->id);

$teilnehmer = $besprechungsteilnehmerRepository->findByBesprechungId($besprechung->id);
$anwesendeTeilnehmer = array_filter($teilnehmer, function ($t) {
    return $t->istAnwesend;
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
                            <h2 class="card-title" style="font-weight: bold;"><?= $besprechung->titel; ?></h2>
                            <div class="card-actions">
                                <button class="btn btn-icon" name="printPDF" id="printPDF"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-type-pdf">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                        <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                                        <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6" />
                                        <path d="M17 18h2" />
                                        <path d="M20 15h-3v6" />
                                        <path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1" />
                                    </svg></button>
                            </div>
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
                                        <?= $translator->t('meetings.participants'); ?>
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
                                                $prefix = ($t->elternPosition ?? 0) != 0 ? $t->elternPosition . '.' . $position : $position;
                                            ?>
                                                <li class="list-group-item">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div>
                                                            <a href="#topic-<?= $prefix ?>" class="text-decoration-none">
                                                                <h4 class="mb-0"><?= $prefix; ?> • <?= $t->titel; ?></h4>
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
                                            <div class="badge bg-secondary text-white"><?= $translator->t('participants.invited'); ?></div>
                                            <div class="badge bg-success text-white"><?= $translator->t('participants.present'); ?></div>
                                            <div class="badge bg-warning text-white"><?= $translator->t('participants.excused'); ?></div>
                                            <div class="badge bg-danger text-white"><?= $translator->t('participants.absent'); ?></div>
                                        </div>
                                    </div>
                                    <div>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($teilnehmer as $t) { ?>
                                                <li class="list-group-item d-flex align-items-center gap-1">
                                                    <span class="badge bg-<?= $t->istAnwesend ? 'success' : ($t->istEntschuldigt ? 'warning' : ($t->istAbwesend ? 'danger' : 'secondary')); ?> text-white"></span>
                                                    <div>
                                                        <?= $t->vorname . ' ' . $t->nachname; ?>
                                                        <div class="text-muted"><?= $t->funktion; ?></div>
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
                                    </svg><strong> <?= $translator->t('status'); ?></strong>
                                </div>
                                <div class="col-7">
                                    <?php
                                    if ($besprechung->status === 'geplant') {
                                        echo '<span class="badge bg-secondary text-white">' . $translator->t('meetings.statusPlanned') . '</span>';
                                    } elseif ($besprechung->status === 'live') {
                                        echo '<span class="badge bg-success text-white">' . $translator->t('meetings.statusLive') . '</span>';
                                    } elseif ($besprechung->status === 'beendet') {
                                        echo '<span class="badge bg-dark text-white">' . $translator->t('meetings.statusFinished') . '</span>';
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
                                    <?= $dauer; ?> <?= $translator->t('minutes'); ?>
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
                                        <span class="badge bg-success text-white"> <?= $translator->t('meetings.published'); ?></span><br />
                                        <a href="<?= $config->get('general', 'url'); ?>/public.php?besprechung=<?= $besprechung->public_code; ?>" target="_blank"><?= $besprechung->public_code; ?></a>
                                    <?php } else { ?>
                                        <span class="badge bg-danger text-white"> <?= $translator->t('meetings.unpublished'); ?></span><br />
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
            </div>

            <div class="hr-text"><?= $translator->translate('meetings.topics') ?></div>

            <div class="row g-3">
                <?php foreach ($tops as $t) {
                    $position = isset($t->position) ? $t->position : ++$i;
                    $prefix = ($t->elternPosition ?? 0) != 0 ? $t->elternPosition . '.' . $position : $position;
                ?>
                    <div class="card mb-2" id="topic-<?= $prefix ?>">
                        <div class="card-header">
                            <h3 class="card-title"><b><?= $prefix; ?> • <?= $t->titel; ?></h3></b>
                        </div>
                        <div class="card-body">
                            <p><?= $t->beschreibung; ?></p>
                            <?php if ($t->ergebnis) { ?>
                                <p><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-sum">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M18 16v2a1 1 0 0 1 -1 1h-11l6 -7l-6 -7h11a1 1 0 0 1 1 1v2" />
                                    </svg> <?= $t->ergebnis; ?></p>
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