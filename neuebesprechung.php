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

if (!$authToManageMeetings) {
    $redirect->to('index.php');
}

$alleBenutzer = $personRepository->findAll();

$start = $_GET['start'] ?? '';


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeetLog</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
</head>

<body>

    <div class="page">
        <div class="sticky-top mb-2">
            <?php include_once 'header.php'; ?>
        </div>

        <div class="container-xl py-3">
            <div class="mb-3 page-title-row">
                <h2 class="page-title mb-0"><?= $translator->t('meetings.create'); ?></h2>
            </div>

            <div class="card">
                <form method="post" action="actions.php?action=create_meeting">
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-12 col-xl-6">
                                <label class="form-label" for="titel-create"><?= $translator->t('meetings.title'); ?></label>
                                <input type="text" name="titel" id="titel-create" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 col-md-4">
                                <label class="form-label" for="datum-create"><?= $translator->t('meetings.date'); ?></label>
                                <input type="date" name="datum" id="datum-create" class="form-control" value="<?= htmlspecialchars($start); ?>">
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

                        <hr class="my-4">
                        <!-- Teilnehmer -->
                        <label class="form-label"><?= $translator->t('meetings.participants.root'); ?></label>
                        <div class="row g-2 align-items-center">
                            <div class="col">
                                <label class="form-label text-secondary"><?= $translator->t('meetings.participants.available'); ?></label>
                                <select id="teilnehmer-verfuegbar" multiple size="10" class="form-select">
                                    <?php foreach ($alleBenutzer as $benutzer): ?>
                                        <option value="<?= $benutzer->id; ?>"><?= htmlspecialchars($benutzer->vorname . ' ' . $benutzer->nachname); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-auto d-flex flex-column gap-2">
                                <button type="button" id="btn-add" class="btn btn-icon" title="<?= $translator->t('meetings.participants.addSelected'); ?>">&gt;</button>
                                <button type="button" id="btn-add-all" class="btn btn-icon" title="<?= $translator->t('meetings.participants.addAll'); ?>">&gt;&gt;</button>
                                <button type="button" id="btn-remove-all" class="btn btn-icon" title="<?= $translator->t('meetings.participants.removeAll'); ?>">&lt;&lt;</button>
                                <button type="button" id="btn-remove" class="btn btn-icon" title="<?= $translator->t('meetings.participants.removeSelected'); ?>">&lt;</button>
                            </div>

                            <div class="col">
                                <label class="form-label text-secondary"><?= $translator->t('meetings.participants.selected'); ?></label>
                                <select id="teilnehmer-ausgewaehlt" name="teilnehmer[]" multiple size="10" class="form-select"></select>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex">
                        <a href="index.php" class="btn me-auto"><?= $translator->t('common.cancel'); ?></a>
                        <button type="submit" class="btn btn-primary"><?= $translator->t('common.save'); ?></button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <?php include_once 'footer.php'; ?>

</body>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const verfuegbar = document.getElementById('teilnehmer-verfuegbar');
        const ausgewaehlt = document.getElementById('teilnehmer-ausgewaehlt');
        const form = ausgewaehlt.closest('form');

        function moveOptions(from, to, onlySelected) {
            Array.from(from.options).forEach(function(option) {
                if (!onlySelected || option.selected) {
                    option.selected = false;
                    to.appendChild(option);
                }
            });
        }

        document.getElementById('btn-add').addEventListener('click', function() {
            moveOptions(verfuegbar, ausgewaehlt, true);
        });

        document.getElementById('btn-remove').addEventListener('click', function() {
            moveOptions(ausgewaehlt, verfuegbar, true);
        });

        document.getElementById('btn-add-all').addEventListener('click', function() {
            moveOptions(verfuegbar, ausgewaehlt, false);
        });

        document.getElementById('btn-remove-all').addEventListener('click', function() {
            moveOptions(ausgewaehlt, verfuegbar, false);
        });

        form.addEventListener('submit', function() {
            Array.from(ausgewaehlt.options).forEach(function(option) {
                option.selected = true;
            });
        });
    });
</script>

</html>