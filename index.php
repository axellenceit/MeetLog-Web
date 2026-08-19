<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

if (!file_exists(__DIR__ . '/install.done')) {
    $redirect->to('install/index.php');
}

use Src\Besprechung\BesprechungRepository;

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

$naechstenBesprechungen = $besprechungRepository->findNextMeetings(5);
$meineAufgaben = $taskRepository->findAllByUserId((int) $session->get('id'));

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
                        <div class="col-12 mb-2">
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

                        <div class="col-12 mb-2">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><?= $translator->t('tasks.my'); ?></h3>
                                    <div class="card-actions">
                                        <a href="./tasks.php?f_titel=&f_user=<?= (int) $session->get('id'); ?>&f_erledigt=" class="btn btn-primary btn-sm"><?= $translator->t('tasks.view_all'); ?></a>
                                    </div>
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

                                                <?php if (empty($meineAufgaben)): ?>
                                                    <div class="list-group-item">
                                                        <div class="row align-items-center">
                                                            <div class="col text-center">
                                                                <span class="text-secondary"><?= $translator->t('tasks.no_tasks'); ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php foreach ($meineAufgaben as $task): ?>

                                                    <?php
                                                    /** @var \Src\Task\Task $task */
                                                    $diff = date_diff(new DateTime(), new DateTime($task->deadline));
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
                                                                <span class="avatar">
                                                                    <?= date('d.m', strtotime($task->deadline)); ?>
                                                                </span>
                                                            </div>
                                                            <div class="col text-truncate">
                                                                <?= $task->titel; ?>
                                                                <div class="d-block text-secondary text-truncate mt-n1">
                                                                    <?= $task->beschreibung ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-auto">
                                                                <input type="checkbox" class="form-check-input" <?= $task->istErledigt ? 'checked' : ''; ?> id="erledigt" name="erledigt" data-task-id="<?= $task->id ?>">
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

<script>
    document.querySelectorAll('input[name="erledigt"]').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            const isChecked = this.checked ? 1 : 0;

            fetch(`actions.php?action=update_task_status&task_id=${taskId}&ist_erledigt=${isChecked}`, {
                    method: 'POST',
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Fehler beim Aktualisieren des Aufgabenstatus.');
                        this.checked = !this.checked; // Revert checkbox state
                    } else {
                        // reload the page to reflect the changes
                        location.reload();
                    }
                })
                .catch(error => {
                    alert('Fehler beim Aktualisieren des Aufgabenstatus.');
                    this.checked = !this.checked; // Revert checkbox state
                });
        });
    });
</script>

</html>