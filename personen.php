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

$organisation = $organisationRepository->findById($organisationId);
if ($organisation === null) {
    $flash->set('errorMsg', 'Organisation nicht gefunden.');
    $flash->set('errorColor', 'red');
    $flash->set('errorCode', '404');
    $redirect->to('error.php');
}

$countries = $countryRepository->findAll();
$mitglieder = $organisationsMitgliedRepository->findAllByOrganisation($organisationId, false);
$organisationsFunktionen = $organisationsFunktionRepository->findAllByOrganisation($organisationId, false);

$funktionenById = [];
foreach ($organisationsFunktionen as $funktion) {
    if ($funktion->id !== null) {
        $funktionenById[(int) $funktion->id] = (string) $funktion->bezeichnung;
    }
}

$personenRows = [];
$userByPerson = [];

foreach ($mitglieder as $mitglied) {
    if ($mitglied->person === null) {
        continue;
    }

    $personId = (int) $mitglied->person;
    if (!isset($userByPerson[$personId])) {
        $userByPerson[$personId] = $userRepository->findByPersonId($personId);
    }

    $personenRows[] = [
        'personId' => $personId,
        'mitgliedId' => (int) ($mitglied->id ?? 0),
        'funktionId' => $mitglied->funktion !== null ? (int) $mitglied->funktion : null,
        'funktionName' => $mitglied->funktion !== null && isset($funktionenById[(int) $mitglied->funktion])
            ? $funktionenById[(int) $mitglied->funktion]
            : '',
        'vorname' => (string) ($mitglied->vorname ?? ''),
        'nachname' => (string) ($mitglied->nachname ?? ''),
        'mail' => (string) ($mitglied->mail ?? ''),
        'telefon' => (string) ($mitglied->telefon ?? ''),
        'strasse' => (string) ($mitglied->strasse ?? ''),
        'plz' => (string) ($mitglied->plz ?? ''),
        'ort' => (string) ($mitglied->ort ?? ''),
        'land' => $mitglied->land !== null ? (int) $mitglied->land : null,
        'istAktiv' => (bool) $mitglied->istAktiv,
        'user' => $userByPerson[$personId],
    ];
}

$filterVorname = trim((string) ($_GET['f_vorname'] ?? ''));
$filterNachname = trim((string) ($_GET['f_nachname'] ?? ''));
$filterMail = trim((string) ($_GET['f_mail'] ?? ''));
$filterTelefon = trim((string) ($_GET['f_telefon'] ?? ''));
$filterFunktion = trim((string) ($_GET['f_funktion'] ?? ''));
$filterAktiv = trim((string) ($_GET['f_aktiv'] ?? ''));
$filterHasUser = trim((string) ($_GET['f_user'] ?? ''));

$personenRows = array_values(array_filter(
    $personenRows,
    static function (array $row) use ($filterVorname, $filterNachname, $filterMail, $filterTelefon, $filterFunktion, $filterAktiv, $filterHasUser): bool {
        if ($filterVorname !== '' && stripos($row['vorname'], $filterVorname) === false) {
            return false;
        }
        if ($filterNachname !== '' && stripos($row['nachname'], $filterNachname) === false) {
            return false;
        }
        if ($filterMail !== '' && stripos($row['mail'], $filterMail) === false) {
            return false;
        }
        if ($filterTelefon !== '' && stripos($row['telefon'], $filterTelefon) === false) {
            return false;
        }
        if ($filterFunktion !== '' && (string) ($row['funktionId'] ?? '') !== $filterFunktion) {
            return false;
        }
        if ($filterAktiv !== '') {
            $isActive = $row['istAktiv'] ? '1' : '0';
            if ($isActive !== $filterAktiv) {
                return false;
            }
        }
        if ($filterHasUser !== '') {
            $hasUser = $row['user'] !== null ? '1' : '0';
            if ($hasUser !== $filterHasUser) {
                return false;
            }
        }

        return true;
    }
));
?>
<!DOCTYPE html>
<html lang="de">

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
                            <?= htmlspecialchars($translator->t('nav.persons', [], 'Personen'), ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                    </div>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-person-create">
                            Person erstellen
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body border-bottom">
                        <form method="get" action="">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_vorname" class="form-label">Vorname</label>
                                    <input type="text" name="f_vorname" id="f_vorname" class="form-control"
                                        value="<?= htmlspecialchars($filterVorname, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_nachname" class="form-label">Nachname</label>
                                    <input type="text" name="f_nachname" id="f_nachname" class="form-control"
                                        value="<?= htmlspecialchars($filterNachname, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_mail" class="form-label">E-Mail</label>
                                    <input type="text" name="f_mail" id="f_mail" class="form-control"
                                        value="<?= htmlspecialchars($filterMail, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_telefon" class="form-label">Telefon</label>
                                    <input type="text" name="f_telefon" id="f_telefon" class="form-control"
                                        value="<?= htmlspecialchars($filterTelefon, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_funktion" class="form-label">Funktion</label>
                                    <select name="f_funktion" id="f_funktion" class="form-select">
                                        <option value="">Alle</option>
                                        <?php foreach ($organisationsFunktionen as $funktion): ?>
                                            <?php $funktionId = (int) ($funktion->id ?? 0); ?>
                                            <option value="<?= $funktionId ?>" <?= $filterFunktion === (string) $funktionId ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string) $funktion->bezeichnung, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_aktiv" class="form-label">Aktiv</label>
                                    <select name="f_aktiv" id="f_aktiv" class="form-select">
                                        <option value="">Alle</option>
                                        <option value="1" <?= $filterAktiv === '1' ? 'selected' : '' ?>>Ja</option>
                                        <option value="0" <?= $filterAktiv === '0' ? 'selected' : '' ?>>Nein</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_user" class="form-label">Benutzerkonto</label>
                                    <select name="f_user" id="f_user" class="form-select">
                                        <option value="">Alle</option>
                                        <option value="1" <?= $filterHasUser === '1' ? 'selected' : '' ?>>Ja</option>
                                        <option value="0" <?= $filterHasUser === '0' ? 'selected' : '' ?>>Nein</option>
                                    </select>
                                </div>

                                <div class="col-12">
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
                                    <th>ID</th>
                                    <th>Vorname</th>
                                    <th>Nachname</th>
                                    <th>E-Mail</th>
                                    <th>Telefon</th>
                                    <th>Funktion</th>
                                    <th>Status</th>
                                    <th>Benutzerkonto</th>
                                    <th class="text-end action-cell">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($personenRows === []): ?>
                                    <tr>
                                        <td colspan="9" class="text-secondary text-center py-4">
                                            Keine Personen gefunden
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($personenRows as $row): ?>
                                    <?php $user = $row['user']; ?>
                                    <tr>
                                        <td><?= (int) $row['personId'] ?></td>
                                        <td><?= htmlspecialchars((string) $row['vorname'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $row['nachname'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $row['mail'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $row['telefon'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($row['funktionName'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php if ($row['istAktiv']): ?>
                                                <span class="badge bg-green-lt">Aktiv</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-lt">Gesperrt</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user !== null): ?>
                                                <span class="badge bg-green-lt">Vorhanden</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-lt">Keins</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end action-cell">
                                            <div class="table-actions">
                                                <button
                                                    type="button"
                                                    class="btn btn-icon btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modal-person-edit"
                                                    data-person-id="<?= (int) $row['personId'] ?>"
                                                    data-person-vorname="<?= htmlspecialchars((string) $row['vorname'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-nachname="<?= htmlspecialchars((string) $row['nachname'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-mail="<?= htmlspecialchars((string) $row['mail'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-telefon="<?= htmlspecialchars((string) $row['telefon'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-strasse="<?= htmlspecialchars((string) $row['strasse'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-plz="<?= htmlspecialchars((string) $row['plz'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-ort="<?= htmlspecialchars((string) $row['ort'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-land="<?= (int) ($row['land'] ?? 0) ?>"
                                                    data-person-funktion-id="<?= (int) ($row['funktionId'] ?? 0) ?>"
                                                    data-person-has-user="<?= $user !== null ? '1' : '0' ?>"
                                                    data-person-has-mail="<?= $row['mail'] !== '' ? '1' : '0' ?>"
                                                    aria-label="Person bearbeiten">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-user-edit">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                        <path d="M6 21v-2a4 4 0 0 1 4 -4h3.5" />
                                                        <path d="M18.42 15.61a2.1 2.1 0 0 1 2.97 2.97l-3.39 3.42h-3v-3l3.42 -3.39" />
                                                    </svg>
                                                </button>

                                                <?php if ($row['istAktiv']): ?>
                                                    <form method="post" action="actions.php?action=disable_person&amp;person_id=<?= (int) $row['personId'] ?>" onsubmit="return confirm('Person wirklich sperren?');">
                                                        <button type="submit" class="btn btn-icon btn-outline-warning" aria-label="Sperren">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-user-x">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                                <path d="M6 21v-2a4 4 0 0 1 4 -4h3.5" />
                                                                <path d="M22 22l-5 -5" />
                                                                <path d="M17 22l5 -5" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="post" action="actions.php?action=enable_person&amp;person_id=<?= (int) $row['personId'] ?>" onsubmit="return confirm('Person wirklich entsperren?');">
                                                        <button type="submit" class="btn btn-icon btn-outline-success" aria-label="Entsperren">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-user-check">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                                <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
                                                                <path d="M15 19l2 2l4 -4" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($user === null): ?>
                                                    <form method="post" action="actions.php?action=create_user_for_person&amp;person_id=<?= (int) $row['personId'] ?>" onsubmit="return confirm('Benutzerkonto für diese Person erstellen?');">
                                                        <button type="submit" class="btn btn-icon btn-outline-success" <?= $row['mail'] !== '' ? '' : 'disabled' ?>>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-plus">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                                <path d="M16 19h6" />
                                                                <path d="M19 16v6" />
                                                                <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="post" action="actions.php?action=reset_password_for_person&amp;person_id=<?= (int) $row['personId'] ?>" onsubmit="return confirm('Passwort zurücksetzen und neues Initialpasswort per E-Mail senden?');">
                                                        <button type="submit" class="btn btn-icon btn-outline-secondary" aria-label="Passwort zurücksetzen" <?= $row['mail'] !== '' ? '' : 'disabled' ?>>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-key">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                                <path d="M6 21v-2a4 4 0 0 1 4 -4h5" />
                                                                <path d="M18.5 18.5l-3.5 3.5l-1.5 -1.5" />
                                                                <path d="M18.554 18.414a2 2 0 1 1 2.828 -2.828a2 2 0 0 1 -2.828 2.828" />
                                                                <path d="M16 19l1 1" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <form method="post" action="actions.php?action=delete_person&amp;person_id=<?= (int) $row['personId'] ?>" onsubmit="return confirm('Person wirklich löschen? Diese Aktion anonymisiert personenbezogene Daten.');">
                                                    <button type="submit" class="btn btn-icon btn-outline-danger" aria-label="Löschen">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-user-minus">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
                                                            <path d="M16 19h6" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-person-create" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="post" action="actions.php?action=create_person">
                    <div class="modal-header">
                        <h5 class="modal-title">Neue Person</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="vorname-create">Vorname</label>
                                <input type="text" name="vorname" id="vorname-create" class="form-control">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="nachname-create">Nachname</label>
                                <input type="text" name="nachname" id="nachname-create" class="form-control">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="strasse-create">Straße</label>
                                <input type="text" name="strasse" id="strasse-create" class="form-control">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="plz-create">PLZ</label>
                                <input type="text" name="plz" id="plz-create" class="form-control">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="ort-create">Ort</label>
                                <input type="text" name="ort" id="ort-create" class="form-control">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="land-create">Land</label>
                                <select name="land" id="land-create" class="form-select">
                                    <option value="">Bitte wählen</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?= (int) $country->countryId ?>">
                                            <?= htmlspecialchars((string) $country->nameDe, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="telefon-create">Telefon</label>
                                <input type="text" name="telefon" id="telefon-create" class="form-control">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="mail-create">E-Mail</label>
                                <input type="email" name="mail" id="mail-create" class="form-control" autocomplete="off">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="funktion-create">Funktion/Rolle</label>
                                <select name="funktion_id" id="funktion-create" class="form-select">
                                    <option value="">Keine Funktion</option>
                                    <?php foreach ($organisationsFunktionen as $funktion): ?>
                                        <option value="<?= (int) ($funktion->id ?? 0) ?>">
                                            <?= htmlspecialchars((string) $funktion->bezeichnung, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="create_user" id="create-user-checkbox" value="1">
                                    <span class="form-check-label">Benutzerkonto für diese Person erstellen</span>
                                </label>
                            </div>

                            <div class="col-12 col-md-6" id="password-container" style="display:none;">
                                <label class="form-label" for="password-create">Initialpasswort</label>
                                <div class="input-group input-group-flat">
                                    <input type="password" name="password" id="password-create" class="form-control" autocomplete="off">
                                    <span class="input-group-text">
                                        <a href="#" class="link-secondary" onclick="generatePassword(); return false;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-password">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M12 10v4" />
                                                <path d="M10 13l4 -2" />
                                                <path d="M10 11l4 2" />
                                                <path d="M5 10v4" />
                                                <path d="M3 13l4 -2" />
                                                <path d="M3 11l4 2" />
                                                <path d="M19 10v4" />
                                                <path d="M17 13l4 -2" />
                                                <path d="M17 11l4 2" />
                                            </svg>
                                        </a>
                                    </span>
                                </div>
                                <small class="form-hint">Leer lassen, um automatisch ein Passwort zu erzeugen.</small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-person-edit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="post" action="actions.php?action=update_person">
                    <div class="modal-header">
                        <h5 class="modal-title">Person bearbeiten</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="person_id" id="person-id-edit" value="">

                        <div class="alert alert-secondary" id="person-edit-account-actions" style="display:none;">
                            <div class="d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-between">
                                <div class="text-secondary" id="person-edit-account-hint"></div>
                                <div class="d-flex gap-2">
                                    <a id="person-edit-create-user-link" class="btn btn-sm btn-outline-primary" href="#" style="display:none;">User anlegen</a>
                                    <a id="person-edit-reset-password-link" class="btn btn-sm btn-outline-secondary" href="#" style="display:none;">Passwort zurücksetzen</a>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="vorname-edit">Vorname</label>
                                <input type="text" name="vorname" id="vorname-edit" class="form-control" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="nachname-edit">Nachname</label>
                                <input type="text" name="nachname" id="nachname-edit" class="form-control" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="strasse-edit">Straße</label>
                                <input type="text" name="strasse" id="strasse-edit" class="form-control" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="plz-edit">PLZ</label>
                                <input type="text" name="plz" id="plz-edit" class="form-control" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="ort-edit">Ort</label>
                                <input type="text" name="ort" id="ort-edit" class="form-control" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="land-edit">Land</label>
                                <select name="land" id="land-edit" class="form-select">
                                    <option value="">Bitte wählen</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?= (int) $country->countryId ?>">
                                            <?= htmlspecialchars((string) $country->nameDe, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="telefon-edit">Telefon</label>
                                <input type="text" name="telefon" id="telefon-edit" class="form-control" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="mail-edit">E-Mail</label>
                                <input type="email" name="mail" id="mail-edit" class="form-control" autocomplete="off" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="funktion-edit">Funktion/Rolle</label>
                                <select name="funktion_id" id="funktion-edit" class="form-select">
                                    <option value="">Keine Funktion</option>
                                    <?php foreach ($organisationsFunktionen as $funktion): ?>
                                        <option value="<?= (int) ($funktion->id ?? 0) ?>">
                                            <?= htmlspecialchars((string) $funktion->bezeichnung, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const createUserCheckbox = document.getElementById('create-user-checkbox');
            const passwordContainer = document.getElementById('password-container');
            const editModal = document.getElementById('modal-person-edit');

            if (createUserCheckbox && passwordContainer) {
                createUserCheckbox.addEventListener('change', function() {
                    passwordContainer.style.display = this.checked ? 'block' : 'none';
                });
            }

            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    if (!button) return;

                    const setValue = (id, value) => {
                        const field = document.getElementById(id);
                        if (field) field.value = value ?? '';
                    };

                    setValue('person-id-edit', button.getAttribute('data-person-id') || '');
                    setValue('vorname-edit', button.getAttribute('data-person-vorname') || '');
                    setValue('nachname-edit', button.getAttribute('data-person-nachname') || '');
                    setValue('mail-edit', button.getAttribute('data-person-mail') || '');
                    setValue('telefon-edit', button.getAttribute('data-person-telefon') || '');
                    setValue('strasse-edit', button.getAttribute('data-person-strasse') || '');
                    setValue('plz-edit', button.getAttribute('data-person-plz') || '');
                    setValue('ort-edit', button.getAttribute('data-person-ort') || '');
                    setValue('land-edit', button.getAttribute('data-person-land') || '');
                    setValue('funktion-edit', button.getAttribute('data-person-funktion-id') || '');

                    const hasUser = (button.getAttribute('data-person-has-user') || '0') === '1';
                    const hasMail = (button.getAttribute('data-person-has-mail') || '0') === '1';
                    const personId = button.getAttribute('data-person-id') || '';

                    const accountBox = document.getElementById('person-edit-account-actions');
                    const accountHint = document.getElementById('person-edit-account-hint');
                    const createUserLink = document.getElementById('person-edit-create-user-link');
                    const resetPasswordLink = document.getElementById('person-edit-reset-password-link');

                    if (accountBox && accountHint && createUserLink && resetPasswordLink) {
                        accountBox.style.display = 'block';
                        createUserLink.style.display = 'none';
                        resetPasswordLink.style.display = 'none';

                        if (!hasUser) {
                            accountHint.textContent = hasMail
                                ? 'Für diese Person existiert noch kein Benutzerkonto.'
                                : 'Für ein Benutzerkonto muss zuerst eine E-Mail-Adresse gepflegt werden.';

                            if (hasMail && personId !== '') {
                                createUserLink.style.display = 'inline-block';
                                createUserLink.href = 'actions.php?action=create_user_for_person&person_id=' + encodeURIComponent(personId);
                            }
                        } else {
                            accountHint.textContent = 'Für diese Person besteht bereits ein Benutzerkonto.';
                            if (hasMail && personId !== '') {
                                resetPasswordLink.style.display = 'inline-block';
                                resetPasswordLink.href = 'actions.php?action=reset_password_for_person&person_id=' + encodeURIComponent(personId);
                            }
                        }
                    }
                });
            }
        });

        function generatePassword() {
            const passwordInput = document.getElementById('password-create');
            if (!passwordInput) return;

            const length = 12;
            const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%+-_';
            let password = '';
            for (let i = 0; i < length; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            passwordInput.type = 'text';
            passwordInput.value = password;
        }
    </script>

    <?php include_once 'footer.php'; ?>
</body>

</html>