<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

$currentURL = basename($_SERVER['SCRIPT_NAME']);
$organizationId = (int) ($session->get('DefaultOrganization') ?? 0);

if ($organizationId <= 0) {
    $flash->set('errorMsg', 'Keine gueltige Standardorganisation gesetzt.');
    $flash->set('errorColor', 'red');
    $flash->set('errorCode', '400');
    $redirect->to('error.php');
}

$organization = $organizationRepository->findById($organizationId);
if ($organization === null) {
    $flash->set('errorMsg', 'Organisation nicht gefunden.');
    $flash->set('errorColor', 'red');
    $flash->set('errorCode', '404');
    $redirect->to('error.php');
}

$countries = $countryRepository->findAll();
$members = $organizationMemberRepository->findAllByOrganization($organizationId, false);
$organizationFunctions = $organizationFunctionRepository->findAllByOrganization($organizationId, false);

$functionsById = [];
foreach ($organizationFunctions as $function) {
    if ($function->id !== null) {
        $functionsById[(int) $function->id] = (string) $function->name;
    }
}

$personRows = [];
$userByPerson = [];

foreach ($members as $member) {
    if ($member->person === null) {
        continue;
    }

    $personId = (int) $member->person;
    if (!isset($userByPerson[$personId])) {
        $userByPerson[$personId] = $userRepository->findByPersonId($personId);
    }

    $personRows[] = [
        'personId' => $personId,
        'memberId' => (int) ($member->id ?? 0),
        'functionId' => $member->function !== null ? (int) $member->function : null,
        'functionName' => $member->function !== null && isset($functionsById[(int) $member->function])
            ? $functionsById[(int) $member->function]
            : '',
        'firstName' => (string) ($member->firstName ?? ''),
        'lastName' => (string) ($member->lastName ?? ''),
        'mail' => (string) ($member->mail ?? ''),
        'phone' => (string) ($member->phone ?? ''),
        'street' => (string) ($member->street ?? ''),
        'zipCode' => (string) ($member->zipCode ?? ''),
        'city' => (string) ($member->city ?? ''),
        'country' => $member->country !== null ? (int) $member->country : null,
        'isActive' => (bool) $member->isActive,
        'user' => $userByPerson[$personId],
    ];
}

$filterFirstName = trim((string) ($_GET['f_firstName'] ?? ''));
$filterLastName = trim((string) ($_GET['f_lastName'] ?? ''));
$filterMail = trim((string) ($_GET['f_mail'] ?? ''));
$filterPhone = trim((string) ($_GET['f_phone'] ?? ''));
$filterFunction = trim((string) ($_GET['f_function'] ?? ''));
$filterActive = trim((string) ($_GET['f_isActive'] ?? ''));
$filterHasUser = trim((string) ($_GET['f_user'] ?? ''));

$personRows = array_values(array_filter(
    $personRows,
    static function (array $row) use ($filterFirstName, $filterLastName, $filterMail, $filterPhone, $filterFunction, $filterActive, $filterHasUser): bool {
        if ($filterFirstName !== '' && stripos($row['firstName'], $filterFirstName) === false) {
            return false;
        }
        if ($filterLastName !== '' && stripos($row['lastName'], $filterLastName) === false) {
            return false;
        }
        if ($filterMail !== '' && stripos($row['mail'], $filterMail) === false) {
            return false;
        }
        if ($filterPhone !== '' && stripos($row['phone'], $filterPhone) === false) {
            return false;
        }
        if ($filterFunction !== '' && (string) ($row['functionId'] ?? '') !== $filterFunction) {
            return false;
        }
        if ($filterActive !== '') {
            $isActive = $row['isActive'] ? '1' : '0';
            if ($isActive !== $filterActive) {
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
    <link rel="icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
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
                                    <label for="f_firstName" class="form-label">Vorname</label>
                                    <input type="text" name="f_firstName" id="f_firstName" class="form-control"
                                        value="<?= htmlspecialchars($filterFirstName, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_lastName" class="form-label">Nachname</label>
                                    <input type="text" name="f_lastName" id="f_lastName" class="form-control"
                                        value="<?= htmlspecialchars($filterLastName, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_mail" class="form-label">E-Mail</label>
                                    <input type="text" name="f_mail" id="f_mail" class="form-control"
                                        value="<?= htmlspecialchars($filterMail, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_phone" class="form-label">Telefon</label>
                                    <input type="text" name="f_phone" id="f_phone" class="form-control"
                                        value="<?= htmlspecialchars($filterPhone, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_function" class="form-label">Funktion</label>
                                    <select name="f_function" id="f_function" class="form-select">
                                        <option value="">Alle</option>
                                        <?php foreach ($organizationFunctions as $function): ?>
                                            <?php $functionId = (int) ($function->id ?? 0); ?>
                                            <option value="<?= $functionId ?>" <?= $filterFunction === (string) $functionId ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string) $function->name, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 col-xl-2">
                                    <label for="f_isActive" class="form-label">Aktiv</label>
                                    <select name="f_isActive" id="f_isActive" class="form-select">
                                        <option value="">Alle</option>
                                        <option value="1" <?= $filterActive === '1' ? 'selected' : '' ?>>Ja</option>
                                        <option value="0" <?= $filterActive === '0' ? 'selected' : '' ?>>Nein</option>
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
                                <?php if ($personRows === []): ?>
                                    <tr>
                                        <td colspan="9" class="text-secondary text-center py-4">
                                            Keine Personen gefunden
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($personRows as $row): ?>
                                    <?php $user = $row['user']; ?>
                                    <tr>
                                        <td><?= (int) $row['personId'] ?></td>
                                        <td><?= htmlspecialchars((string) $row['firstName'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $row['lastName'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $row['mail'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $row['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($row['functionName'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php if ($row['isActive']): ?>
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
                                                    data-person-first-name="<?= htmlspecialchars((string) $row['firstName'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-last-name="<?= htmlspecialchars((string) $row['lastName'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-mail="<?= htmlspecialchars((string) $row['mail'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-phone="<?= htmlspecialchars((string) $row['phone'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-street="<?= htmlspecialchars((string) $row['street'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-zip-code="<?= htmlspecialchars((string) $row['zipCode'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-city="<?= htmlspecialchars((string) $row['city'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-person-country="<?= (int) ($row['country'] ?? 0) ?>"
                                                    data-person-function-id="<?= (int) ($row['functionId'] ?? 0) ?>"
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

                                                <?php if ($row['isActive']): ?>
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
                                <label class="form-label" for="firstName-create">Vorname</label>
                                <input type="text" name="firstName" id="firstName-create" class="form-control">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="lastName-create">Nachname</label>
                                <input type="text" name="lastName" id="lastName-create" class="form-control">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="street-create">Straße</label>
                                <input type="text" name="street" id="street-create" class="form-control">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="zipCode-create">PLZ</label>
                                <input type="text" name="zipCode" id="zipCode-create" class="form-control">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="city-create">Ort</label>
                                <input type="text" name="city" id="city-create" class="form-control">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="country-create">Land</label>
                                <select name="country" id="country-create" class="form-select">
                                    <option value="">Bitte wählen</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?= (int) $country->countryId ?>">
                                            <?= htmlspecialchars((string) $country->nameDe, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="phone-create">Telefon</label>
                                <input type="text" name="phone" id="phone-create" class="form-control">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="mail-create">E-Mail</label>
                                <input type="email" name="mail" id="mail-create" class="form-control" autocomplete="off">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="function-create">Funktion/Rolle</label>
                                <select name="function_id" id="function-create" class="form-select">
                                    <option value="">Keine Funktion</option>
                                    <?php foreach ($organizationFunctions as $function): ?>
                                        <option value="<?= (int) ($function->id ?? 0) ?>">
                                            <?= htmlspecialchars((string) $function->name, ENT_QUOTES, 'UTF-8') ?>
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
                                <label class="form-label" for="firstName-edit">Vorname</label>
                                <input type="text" name="firstName" id="firstName-edit" class="form-control" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="lastName-edit">Nachname</label>
                                <input type="text" name="lastName" id="lastName-edit" class="form-control" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="street-edit">Straße</label>
                                <input type="text" name="street" id="street-edit" class="form-control" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="zipCode-edit">PLZ</label>
                                <input type="text" name="zipCode" id="zipCode-edit" class="form-control" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="city-edit">Ort</label>
                                <input type="text" name="city" id="city-edit" class="form-control" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="country-edit">Land</label>
                                <select name="country" id="country-edit" class="form-select">
                                    <option value="">Bitte wählen</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?= (int) $country->countryId ?>">
                                            <?= htmlspecialchars((string) $country->nameDe, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="phone-edit">Telefon</label>
                                <input type="text" name="phone" id="phone-edit" class="form-control" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="mail-edit">E-Mail</label>
                                <input type="email" name="mail" id="mail-edit" class="form-control" autocomplete="off" value="">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="function-edit">Funktion/Rolle</label>
                                <select name="function_id" id="function-edit" class="form-select">
                                    <option value="">Keine Funktion</option>
                                    <?php foreach ($organizationFunctions as $function): ?>
                                        <option value="<?= (int) ($function->id ?? 0) ?>">
                                            <?= htmlspecialchars((string) $function->name, ENT_QUOTES, 'UTF-8') ?>
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
                    setValue('firstName-edit', button.getAttribute('data-person-first-name') || '');
                    setValue('lastName-edit', button.getAttribute('data-person-last-name') || '');
                    setValue('mail-edit', button.getAttribute('data-person-mail') || '');
                    setValue('phone-edit', button.getAttribute('data-person-phone') || '');
                    setValue('street-edit', button.getAttribute('data-person-street') || '');
                    setValue('zipCode-edit', button.getAttribute('data-person-zip-code') || '');
                    setValue('city-edit', button.getAttribute('data-person-city') || '');
                    setValue('country-edit', button.getAttribute('data-person-country') || '');
                    setValue('function-edit', button.getAttribute('data-person-function-id') || '');

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
                            accountHint.textContent = hasMail ?
                                'Für diese Person existiert noch kein Benutzerkonto.' :
                                'Für ein Benutzerkonto muss zuerst eine E-Mail-Adresse gepflegt werden.';

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