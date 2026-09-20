<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

if (!file_exists(__DIR__ . '/install.done')) {
    $redirect->to('install/index.php');
}

use Src\User\Permission;

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

$user = $userRepository->findById($session->get('id'));

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
                            <span class="text-muted"><?= $translator->t('common.calendar.instructions'); ?></span>
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
                                                                <a href="#" class="task-title-link" data-bs-toggle="modal" data-bs-target="#modal-task-view"
                                                                    data-task-id="<?= $task->id ?>"
                                                                    data-task-title="<?= htmlspecialchars($task->titel, ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-task-description="<?= htmlspecialchars($task->beschreibung ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-task-deadline="<?= htmlspecialchars($task->deadline ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-task-completed="<?= $task->istErledigt ? '1' : '0' ?>"><?= $task->titel; ?></a>
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

            <div class="modal modal-blur fade" id="modal-task-view" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= $translator->t('tasks.edit'); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="task-title-view"><?= $translator->t('tasks.title'); ?></label>
                                    <input class="form-control" type="text" id="task-title-view" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="task-description-view"><?= $translator->t('tasks.description'); ?></label>
                                    <textarea class="form-control" id="task-description-view" rows="4" readonly></textarea>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="task-deadline-view"><?= $translator->t('tasks.dueDate'); ?></label>
                                    <input class="form-control" type="date" id="task-deadline-view" readonly>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="task-completed-view"><?= $translator->t('tasks.status'); ?></label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="task-completed-view" data-task-id="">
                                        <label class="form-check-label" for="task-completed-view"><?= $translator->t('tasks.status'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn me-auto" data-bs-dismiss="modal"><?= $translator->t('common.cancel'); ?></button>
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
            selectable: true,
            select: function(info) {
                window.location.href = './neuebesprechung.php?start=' + info.startStr;
            },
            locale: '<?= $session->get('lang'); ?>',
            displayEventTime: false,
            weekNumbers: true,
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const viewModal = document.getElementById('modal-task-view');
        if (!viewModal) return;

        const titleInput = document.getElementById('task-title-view');
        const descriptionInput = document.getElementById('task-description-view');
        const deadlineInput = document.getElementById('task-deadline-view');
        const completedCheckbox = document.getElementById('task-completed-view');

        viewModal.addEventListener('show.bs.modal', function(event) {
            const link = event.relatedTarget;
            if (!link) return;

            titleInput.value = link.getAttribute('data-task-title') || '';
            descriptionInput.value = link.getAttribute('data-task-description') || '';
            deadlineInput.value = link.getAttribute('data-task-deadline') || '';
            completedCheckbox.checked = link.getAttribute('data-task-completed') === '1';
            completedCheckbox.dataset.taskId = link.getAttribute('data-task-id') || '';
        });

        completedCheckbox.addEventListener('change', function() {
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