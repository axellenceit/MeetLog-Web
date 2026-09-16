<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

$currentURL = basename($_SERVER['SCRIPT_NAME']);
$organisationId = (int) ($session->get('StandardOrganisation') ?? 0);

if ($organisationId <= 0) {
    $flash->set('errorMsg', 'Keine gueltige Standardorganisation gesetzt.');
    $flash->set('errorColor', 'red');
    $flash->set('errorCode', '400');
    $redirect->to('error.php');
}

$meetings = $besprechungRepository->findByOrganisationId($organisationId);
$users = $userRepository->findAll(true);
$meetingsById = [];
$usersById = [];

foreach ($meetings as $meeting) {
    if ($meeting->id !== null) {
        $meetingsById[$meeting->id] = $meeting;
    }
}

foreach ($users as $user) {
    if ($user->id !== null) {
        $usersById[$user->id] = $user;
    }
}

$filterTitel = $_GET['f_titel'] ?? '';
$filterUser = $_GET['f_user'] ?? '';
$filterErledigt = $_GET['f_erledigt'] ?? '';

$tasks = $taskRepository->findAllWithFilter(
    $filterTitel !== '' ? $filterTitel : null,
    $filterUser !== '' ? (int)$filterUser : null,
    $filterErledigt !== '' ? (bool)$filterErledigt : null
);

$tasks = array_values(array_filter(
    $tasks,
    static fn($task): bool => $task->besprechungId !== null && isset($meetingsById[$task->besprechungId])
));

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeetLog</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">


    <style>
        .page-title-row {
            gap: 1rem;
        }

        .action-cell {
            min-width: 260px;
        }

        .table-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            justify-content: flex-end;
        }

        @media (max-width: 767.98px) {
            .page-title-row {
                flex-direction: column;
                align-items: stretch !important;
            }

            .page-title-row>div:last-child .btn {
                width: 100%;
            }

            .table-actions {
                justify-content: flex-start;
            }
        }
    </style>
</head>

<body>

    <div class="page">
        <div class="sticky-top mb-2">
            <?php include_once 'header.php'; ?>
        </div>

        <div class="page-wrapper">
            <div class="container-xl py-3">

                <div class="d-flex justify-content-between align-items-center mb-3 page-title-row">
                    <div>
                        <h2 class="page-title mb-0">
                            <?= htmlspecialchars($translator->t('nav.tasks'), ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                    </div>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-task-create" <?= $meetings === [] ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($translator->t('tasks.create'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body border-bottom">
                        <form method="get" action="">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_titel" class="form-label">Titel</label>
                                    <input type="text" name="f_titel" id="f_titel" class="form-control"
                                        value="<?= htmlspecialchars($filterTitel, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_user" class="form-label">Zugewiesene Person</label>
                                    <select class="form-select" name="f_user" id="f_user">
                                        <option value="">Nicht zugewiesen</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= (int) $user->id ?>" <?= $filterUser === (string) $user->id ? 'selected' : '' ?>><?= htmlspecialchars(trim($user->vorname . ' ' . $user->nachname), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_erledigt" class="form-label">Erledigt</label>
                                    <select name="f_erledigt" id="f_erledigt" class="form-select">
                                        <option value="">Alle</option>
                                        <option value="1" <?= $filterErledigt === '1' ? 'selected' : '' ?>>Ja</option>
                                        <option value="0" <?= $filterErledigt === '0' ? 'selected' : '' ?>>Nein</option>
                                    </select>
                                </div>

                                <div class="col-6 col-md-3 col-xl-2">
                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <button type="submit" class="btn btn-primary">Suchen</button>
                                        <a href="<?= htmlspecialchars($currentURL, ENT_QUOTES, 'UTF-8') ?>"
                                            class="btn btn-outline-secondary">Zurücksetzen</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Erl.</th>
                                    <th>Titel</th>
                                    <th>Besprechung</th>
                                    <th>Verantwortlich</th>
                                    <th>Fällig</th>
                                    <th class="text-end action-cell">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($tasks === []): ?>
                                    <tr>
                                        <td colspan="6" class="text-secondary text-center py-4">Keine Aufgaben gefunden</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($tasks as $task): ?>
                                    <?php
                                    $meeting = $meetingsById[$task->besprechungId] ?? null;
                                    $responsibleUser = $task->verantwortlicherUserId !== null ? ($usersById[$task->verantwortlicherUserId] ?? null) : null;
                                    ?>
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input" id="erledigt" name="erledigt" data-task-id="<?= $task->id ?>" <?= $task->istErledigt ? 'checked' : '' ?>></td>
                                        <td <?php if ($task->istErledigt) echo 'class="text-decoration-line-through"'; ?>>
                                            <div><?= htmlspecialchars($task->titel, ENT_QUOTES, 'UTF-8') ?></div>
                                        </td>
                                        <td <?php if ($task->istErledigt) echo 'class="text-decoration-line-through"'; ?>><?= htmlspecialchars($meeting?->titel ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td <?php if ($task->istErledigt) echo 'class="text-decoration-line-through"'; ?>><?= htmlspecialchars($responsibleUser !== null ? trim($responsibleUser->vorname . ' ' . $responsibleUser->nachname) : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td <?php if ($task->istErledigt) echo 'class="text-decoration-line-through"'; ?>><?= htmlspecialchars($task->deadline ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end action-cell">
                                            <div class="table-actions">
                                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-task-edit"
                                                    data-task-id="<?= (int) $task->id ?>"
                                                    data-task-meeting-id="<?= (int) $task->besprechungId ?>"
                                                    data-task-user-id="<?= (int) ($task->verantwortlicherUserId ?? 0) ?>"
                                                    data-task-title="<?= htmlspecialchars($task->titel, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-task-description="<?= htmlspecialchars($task->beschreibung ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-task-deadline="<?= htmlspecialchars($task->deadline ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-task-completed="<?= $task->istErledigt ? '1' : '0' ?>">Bearbeiten</button>
                                                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modal-task-delete"
                                                    data-task-id="<?= (int) $task->id ?>"
                                                    data-task-title="<?= htmlspecialchars($task->titel, ENT_QUOTES, 'UTF-8') ?>">Löschen</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal modal-blur fade" id="modal-task-create" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <form method="post" action="actions.php?action=create_task">
                            <div class="modal-header">
                                <h5 class="modal-title"><?= $translator->t('tasks.create'); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6"><label class="form-label" for="task-meeting-create">Besprechung</label>
                                        <input type="hidden" name="besprechung_id" id="task-meeting-id-create">
                                        <input class="form-control" id="task-meeting-create" autocomplete="off">
                                    </div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="task-user-create">Verantwortlich</label>
                                        <select class="form-select" name="verantwortlicher_user_id" id="task-user-create">
                                            <option value="">Nicht zugewiesen</option>
                                            <?php foreach ($users as $user): ?><option value="<?= (int) $user->id ?>"><?= htmlspecialchars(trim($user->vorname . ' ' . $user->nachname), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12"><label class="form-label" for="task-title-create">Titel</label><input class="form-control" type="text" name="titel" id="task-title-create" maxlength="255" required></div>
                                    <div class="col-12"><label class="form-label" for="task-description-create">Beschreibung</label><textarea class="form-control" name="beschreibung" id="task-description-create" rows="4"></textarea></div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="task-deadline-create">Fälligkeitsdatum</label><input class="form-control" type="date" name="deadline" id="task-deadline-create"></div>
                                </div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn me-auto" data-bs-dismiss="modal"><?= $translator->t('common.cancel'); ?></button><button type="submit" class="btn btn-primary"><?= $translator->t('common.save'); ?></button></div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal modal-blur fade" id="modal-task-edit" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <form method="post" action="actions.php?action=update_task">
                            <div class="modal-header">
                                <h5 class="modal-title"><?= $translator->t('tasks.edit'); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="task_id" id="task-id-edit">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6"><label class="form-label" for="task-meeting-edit">Besprechung</label><select class="form-select" name="besprechung_id" id="task-meeting-edit" required>
                                            <option value="">Bitte wählen</option>
                                            <?php foreach ($meetings as $meeting): ?>
                                                <option value="<?= (int) $meeting->id ?>"><?= htmlspecialchars($meeting->titel, ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach; ?>
                                        </select></div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="task-user-edit">Verantwortlich</label><select class="form-select" name="verantwortlicher_user_id" id="task-user-edit">
                                            <option value="">Nicht zugewiesen</option><?php foreach ($users as $user): ?><option value="<?= (int) $user->id ?>"><?= htmlspecialchars(trim($user->vorname . ' ' . $user->nachname), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                                        </select></div>
                                    <div class="col-12"><label class="form-label" for="task-title-edit">Titel</label><input class="form-control" type="text" name="titel" id="task-title-edit" maxlength="255" required></div>
                                    <div class="col-12"><label class="form-label" for="task-description-edit">Beschreibung</label><textarea class="form-control" name="beschreibung" id="task-description-edit" rows="4"></textarea></div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="task-deadline-edit">Fälligkeitsdatum</label><input class="form-control" type="date" name="deadline" id="task-deadline-edit"></div>
                                    <div class="col-12 col-md-6"><label class="form-label" for="task-completed-edit">Status</label><select class="form-select" name="ist_erledigt" id="task-completed-edit">
                                            <option value="0">Offen</option>
                                            <option value="1">Erledigt</option>
                                        </select></div>
                                </div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn me-auto" data-bs-dismiss="modal"><?= $translator->t('common.cancel'); ?></button><button type="submit" class="btn btn-primary"><?= $translator->t('common.save'); ?></button></div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal modal-blur fade" id="modal-task-delete" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <form method="post" action="actions.php?action=delete_task">
                            <div class="modal-header">
                                <h5 class="modal-title"><?= $translator->t('tasks.delete'); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                            </div>
                            <div class="modal-body"><input type="hidden" name="task_id" id="task-id-delete">
                                <p class="mb-0">Soll die Aufgabe <strong id="task-title-delete"></strong> wirklich gelöscht werden?</p>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn me-auto" data-bs-dismiss="modal"><?= $translator->t('common.cancel'); ?></button><button type="submit" class="btn btn-danger"><?= $translator->t('common.delete'); ?></button></div>
                        </form>
                    </div>
                </div>
            </div>

            <?php include_once 'footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const meetingInput = document.getElementById('task-meeting-create');
            const meetingIdInput = document.getElementById('task-meeting-id-create');
            const meetings = <?= json_encode(array_map(static fn($meeting): array => ['id' => $meeting->id, 'title' => $meeting->titel], $meetings), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

            if (meetingInput && meetingIdInput) {
                meetingInput.addEventListener('input', function() {
                    const typedValue = this.value;
                    meetingIdInput.value = '';

                    const matchingMeeting = meetings.find(function(meeting) {
                        return meeting.title.toLocaleLowerCase().startsWith(typedValue.toLocaleLowerCase());
                    });

                    if (typedValue !== '' && matchingMeeting) {
                        meetingIdInput.value = matchingMeeting.id;

                        if (matchingMeeting.title !== typedValue) {
                            this.value = matchingMeeting.title;
                            this.setSelectionRange(typedValue.length, matchingMeeting.title.length);
                        }
                    }
                });
            }

            const editModal = document.getElementById('modal-task-edit');
            const deleteModal = document.getElementById('modal-task-delete');

            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    if (!button) return;
                    document.getElementById('task-id-edit').value = button.getAttribute('data-task-id') || '';
                    document.getElementById('task-meeting-edit').value = button.getAttribute('data-task-meeting-id') || '';
                    document.getElementById('task-user-edit').value = button.getAttribute('data-task-user-id') || '';
                    document.getElementById('task-title-edit').value = button.getAttribute('data-task-title') || '';
                    document.getElementById('task-description-edit').value = button.getAttribute('data-task-description') || '';
                    document.getElementById('task-deadline-edit').value = button.getAttribute('data-task-deadline') || '';
                    document.getElementById('task-completed-edit').value = button.getAttribute('data-task-completed') || '0';
                });
            }

            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    if (!button) return;
                    document.getElementById('task-id-delete').value = button.getAttribute('data-task-id') || '';
                    document.getElementById('task-title-delete').textContent = button.getAttribute('data-task-title') || '';
                });
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name="erledigt"]');

            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const taskId = this.getAttribute('data-task-id');
                    const isCompleted = this.checked ? 1 : 0;

                    fetch('actions.php?action=update_task_status', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `task_id=${encodeURIComponent(taskId)}&ist_erledigt=${encodeURIComponent(isCompleted)}`,
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
                            console.error('Error:', error);
                            alert('Fehler beim Aktualisieren des Aufgabenstatus.');
                            this.checked = !this.checked; // Revert checkbox state
                        });
                });
            });
        });
    </script>
</body>

</html>