<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

use Src\Besprechung\BesprechungRepository;

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

$naechstenBesprechungen = $besprechungRepository->findNextMeetings(5);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeetLog</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">

    <style>
        .fc .fc-daygrid-event,
        .fc .fc-daygrid-dot-event {
            max-width: 100%;
            overflow: hidden;
        }

        .fc .fc-event-main {
            overflow: hidden;
        }

        .fc .fc-event-wrap {
            max-width: 100%;
            overflow: hidden;
        }

        .fc .fc-event-time-custom,
        .fc .fc-event-title-custom {
            display: block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>

</head>

<body>

    <div class="page">
        <div class="sticky-top mb-2">
            <?php include_once 'header.php'; ?>
        </div>

        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="row row-cards">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><?= $translator->t('meetings.next'); ?></h3>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <div class="row">
                                            <div class="col-auto">
                                                <span class="badge bg-green"></span> > 14 <?= $translator->t('meetings.days'); ?>
                                            </div>
                                            <div class="col-auto">
                                                <span class="badge bg-yellow"></span> > 7 <?= $translator->t('meetings.days'); ?>
                                            </div>
                                            <div class="col-auto">
                                                <span class="badge bg-red"></span>
                                                <= 7 <?= $translator->t('meetings.days'); ?>
                                                    </div>
                                            </div>
                                            <div class="list-group list-group-flush list-group-hoverable">

                                                <?php if (empty($naechstenBesprechungen)): ?>
                                                    <div class="list-group-item">
                                                        <div class="row align-items-center">
                                                            <div class="col text-center">
                                                                <span class="text-secondary"><?= $translator->t('meetings.no_next'); ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php foreach ($naechstenBesprechungen as $besprechung): ?>

                                                    <?php
                                                    /** @var \Src\Besprechung\Besprechung $besprechung */
                                                    $diff = date_diff(new DateTime(), new DateTime($besprechung->datum));
                                                    $eventColor = match (true) {
                                                        $diff->days > 14 => 'bg-green',
                                                        $diff->days > 7 => 'bg-yellow',
                                                        default => 'bg-red',
                                                    };
                                                    ?>
                                                    <div class="list-group-item">
                                                        <div class="row align-items-center">
                                                            <div class="col-auto"><span class="badge <?= $eventColor ?>"></span></div>
                                                            <div class="col-auto">
                                                                <a href="#">
                                                                    <span class="avatar">
                                                                        <?= date('d.m', strtotime($besprechung->datum)); ?>
                                                                    </span>
                                                                </a>
                                                            </div>
                                                            <div class="col text-truncate">
                                                                <a href="./besprechung.php?id=<?= $besprechung->id; ?>"><?= $besprechung->titel; ?></a>
                                                                <div class="d-block text-secondary text-truncate mt-n1">
                                                                    <?= $besprechung->ort ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <?php include_once 'footer.php'; ?>

</body>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
<script src="./node_modules/fullcalendar/index.global.min.js"></script>
<script src="./node_modules/@fullcalendar/core/locales/de.global.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            timeZone: 'local',
            events: './api/fullcalendar_getEvents.php',
            locale: '<?= $session->get('lang'); ?>',
            displayEventTime: false,
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                window.location.href = './besprechung.php?id=' + info.event.id;
            },
            eventContent: function(info) {
                const start = info.event.start;
                const uhrzeit = start ?
                    String(start.getHours()).padStart(2, '0') + ':' +
                    String(start.getMinutes()).padStart(2, '0') :
                    '';

                return {
                    html: `
                        <div class="fc-event-wrap">
                            <div class="fc-event-time-custom">${uhrzeit}</div>
                            <div class="fc-event-title-custom">${info.event.title}</div>
                        </div>
                        `
                };
            },
            eventDidMount: function(info) {
                info.el.setAttribute('title', info.event.title);
            }
        });
        calendar.render();
        calendar.setOption('locale', '<?= $session->get('lang'); ?>');
    });
</script>

</html>