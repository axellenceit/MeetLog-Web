<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

if (!$auth->isLoggedIn()) {
    $redirect->to('login.php');
}

use Src\Organization\OrganizationFunction;

$currentURL = basename($_SERVER['SCRIPT_NAME']);
$organizationId = (int) ($session->get('DefaultOrganization') ?? 0);

if ($organizationId <= 0) {
    $flash->set('organization_error', 'Keine gültige Standardorganisation gesetzt.');
    $redirect->to($currentURL . '?page=overview');
}

$organization = $organizationRepository->findById($organizationId);
if ($organization === null) {
    $flash->set('organization_error', 'Organisation nicht gefunden.');
    $redirect->to($currentURL . '?page=overview');
}

$organizationFunctions = $organizationFunctionRepository->findAllByOrganization($organizationId, false);

$roleFilterName = trim($_GET['f_name'] ?? '');
$roleFilterActive = trim($_GET['f_isActive'] ?? '');

$organizationFunctionsFiltered = array_values(array_filter(
    $organizationFunctions,
    static function ($function) use ($roleFilterName, $roleFilterActive): bool {
        if ($roleFilterName !== '' && stripos((string) ($function->name ?? ''), $roleFilterName) === false) {
            return false;
        }

        if ($roleFilterActive !== '') {
            $isActive = $function->isActive ? '1' : '0';
            if ($isActive !== $roleFilterActive) {
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
        .action-cell {
            min-width: 180px;
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
                gap: .75rem;
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
                <ul class="nav nav-bordered mb-4 flex-nowrap overflow-auto">
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="./organization.php">
                            <?= htmlspecialchars($translator->t('organization.overview'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="./organization.functions.php">
                            <?= htmlspecialchars($translator->t('organization.functions.root'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                </ul>

                <div class="d-flex justify-content-between align-items-center mb-3 page-title-row">
                    <div>
                        <h2 class="page-title mb-0">
                            <?= htmlspecialchars($translator->t('organization.functions.root'), ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                    </div>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-role-create">
                            <?= $translator->t('organization.functions.new') ?>
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body border-bottom">
                        <form method="get" action="">
                            <input type="hidden" name="page" value="functions">

                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-6 col-lg-5">
                                    <label for="f_name" class="form-label"><?= $translator->t('organization.functions.designation') ?></label>
                                    <input
                                        type="text"
                                        name="f_name"
                                        id="f_name"
                                        class="form-control"
                                        value="<?= htmlspecialchars($roleFilterName, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6 col-lg-3">
                                    <label for="f_isActive" class="form-label"><?= $translator->t('organization.functions.active') ?></label>
                                    <select name="f_isActive" id="f_isActive" class="form-select">
                                        <option value=""><?= $translator->t('organization.functions.all') ?></option>
                                        <option value="1" <?= $roleFilterActive === '1' ? 'selected' : '' ?>><?= $translator->t('organization.functions.yes') ?></option>
                                        <option value="0" <?= $roleFilterActive === '0' ? 'selected' : '' ?>><?= $translator->t('organization.functions.no') ?></option>
                                    </select>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <?= $translator->t('organization.functions.search') ?>
                                        </button>
                                        <a href="<?= htmlspecialchars($currentURL, ENT_QUOTES, 'UTF-8') ?>?page=functions"
                                            class="btn btn-outline-secondary">
                                            <?= $translator->t('organization.functions.reset') ?>
                                        </a>
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
                                    <th><?= $translator->t('organization.functions.designation') ?></th>
                                    <th><?= $translator->t('organization.functions.description') ?></th>
                                    <th><?= $translator->t('organization.functions.status') ?></th>
                                    <th class="text-end action-cell"><?= $translator->t('organization.functions.actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($organizationFunctionsFiltered === []): ?>
                                    <tr>
                                        <td colspan="5" class="text-secondary text-center py-4">
                                            <?= $translator->t('functions.nothingfound') ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($organizationFunctionsFiltered as $function): ?>
                                    <tr>
                                        <td><?= (int) $function->id ?></td>
                                        <td>
                                            <?= htmlspecialchars((string) $function->name, ENT_QUOTES, 'UTF-8') ?>
                                            <?php if ($function->maySign): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-signature" data-bs-toggle="tooltip" data-bs-original-title="<?= $translator->t('organization.functions.authToSign') ?>">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2" />
                                                </svg>
                                            <?php endif; ?>
                                            <?php if ($function->mayManageOrganization): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-building-cog" data-bs-toggle="tooltip" data-bs-original-title="<?= $translator->t('organization.functions.authToManageOrganization') ?>">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M3 21h9" />
                                                    <path d="M9 8h1" />
                                                    <path d="M9 12h1" />
                                                    <path d="M9 16h1" />
                                                    <path d="M14 8h1" />
                                                    <path d="M14 12h1" />
                                                    <path d="M5 21v-16c0 -.53 .211 -1.039 .586 -1.414c.375 -.375 .884 -.586 1.414 -.586h10c.53 0 1.039 .211 1.414 .586c.375 .375 .586 .884 .586 1.414v7" />
                                                    <path d="M16 18c0 .53 .211 1.039 .586 1.414c.375 .375 .884 .586 1.414 .586c.53 0 1.039 -.211 1.414 -.586c.375 -.375 .586 -.884 .586 -1.414c0 -.53 -.211 -1.039 -.586 -1.414c-.375 -.375 -.884 -.586 -1.414 -.586c-.53 0 -1.039 .211 -1.414 .586c-.375 .375 -.586 .884 -.586 1.414" />
                                                    <path d="M18 14.5v1.5" />
                                                    <path d="M18 20v1.5" />
                                                    <path d="M21.032 16.25l-1.299 .75" />
                                                    <path d="M16.27 19l-1.3 .75" />
                                                    <path d="M14.97 16.25l1.3 .75" />
                                                    <path d="M19.733 19l1.3 .75" />
                                                </svg>
                                            <?php endif; ?>
                                            <?php if ($function->mayManageMeetings): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-cog" data-bs-toggle="tooltip" data-bs-original-title="<?= $translator->t('organization.functions.authToManageMeetings') ?>">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M12 21h-6a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v5" />
                                                    <path d="M16 3v4" />
                                                    <path d="M8 3v4" />
                                                    <path d="M4 11h16" />
                                                    <path d="M17.001 19a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                                    <path d="M19.001 15.5v1.5" />
                                                    <path d="M19.001 21v1.5" />
                                                    <path d="M22.032 17.25l-1.299 .75" />
                                                    <path d="M17.27 20l-1.3 .75" />
                                                    <path d="M15.97 17.25l1.3 .75" />
                                                    <path d="M20.733 20l1.3 .75" />
                                                </svg>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars((string) ($function->description ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php if ($function->isActive): ?>
                                                <span class="badge bg-green-lt"><?= $translator->t('organization.functions.active'); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-lt"><?= $translator->t('organization.functions.inactive'); ?></span>
                                            <?php endif; ?>

                                        </td>
                                        <td class="text-end action-cell">
                                            <div class="table-actions">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modal-role-edit-<?= (int) $function->id ?>">
                                                    <?= $translator->t('organization.functions.edit') ?>
                                                </button>

                                                <form method="post" action="" onsubmit="return confirm('<?= $translator->t('organization.functions.delete') ?>?');">
                                                    <input type="hidden" name="role_action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int) $function->id ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <?= $translator->t('organization.functions.delete') ?>
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

                <?php foreach ($organizationFunctionsFiltered as $function): ?>
                    <div class="modal modal-blur fade" id="modal-role-edit-<?= (int) $function->id ?>" tabindex="-1"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <form method="post" action="">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?= $translator->t('organization.functions.edit') ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="<?= $translator->t('close'); ?>"></button>
                                    </div>

                                    <div class="modal-body">
                                        <input type="hidden" name="role_action" value="save">
                                        <input type="hidden" name="id" value="<?= (int) $function->id ?>">

                                        <div class="row g-3">
                                            <div class="col-12 col-md-8">
                                                <label class="form-label" for="name-<?= (int) $function->id ?>"><?= $translator->t('organization.functions.designation'); ?></label>
                                                <input
                                                    type="text"
                                                    name="name"
                                                    id="name-<?= (int) $function->id ?>"
                                                    class="form-control"
                                                    value="<?= htmlspecialchars((string) $function->name, ENT_QUOTES, 'UTF-8') ?>">
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label" for="description-<?= (int) $function->id ?>"><?= $translator->t('organization.functions.description'); ?></label>
                                                <textarea
                                                    name="description"
                                                    id="description-<?= (int) $function->id ?>"
                                                    class="form-control"
                                                    rows="4"><?= htmlspecialchars((string) ($function->description ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                            </div>

                                            <div class="col-12 col-md-4 d-flex align-items-md-end">
                                                <label class="form-check mb-2 mb-md-0"><?= $translator->t('organization.functions.isActive'); ?></label>
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    name="isActive"
                                                    value="1"
                                                    <?= $function->isActive ? 'checked' : '' ?>>
                                                <span class="form-check-label"><?= $translator->t('organization.functions.active'); ?></span>
                                                </label>
                                            </div>

                                            <div class="col-12 col-md-4 d-flex align-items-md-end">
                                                <label class="form-check mb-2 mb-md-0">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="maySign"
                                                        value="1"
                                                        <?= $function->maySign ? 'checked' : '' ?>>
                                                    <span class="form-check-label"><?= $translator->t('organization.functions.authToSign'); ?></span>
                                                </label>
                                            </div>

                                            <div class="col-12 col-md-4 d-flex align-items-md-end">
                                                <label class="form-check mb-2 mb-md-0">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="mayManageOrganization"
                                                        value="1"
                                                        <?= $function->mayManageOrganization ? 'checked' : '' ?>>
                                                    <span class="form-check-label"><?= $translator->t('organization.functions.authToManageOrganization'); ?></span>
                                                </label>
                                            </div>

                                            <div class="col-12 col-md-4 d-flex align-items-md-end">
                                                <label class="form-check mb-2 mb-md-0">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="mayManageMeetings"
                                                        value="1"
                                                        <?= $function->mayManageMeetings ? 'checked' : '' ?>>
                                                    <span class="form-check-label"><?= $translator->t('organization.functions.authToManageMeetings'); ?></span>
                                                </label>
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
                <?php endforeach; ?>

                <div class="modal modal-blur fade" id="modal-role-create" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <form method="post" action="">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?= $translator->t('organization.functions.new'); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Schließen"></button>
                                </div>

                                <div class="modal-body">
                                    <input type="hidden" name="role_action" value="save">

                                    <div class="row g-3">
                                        <div class="col-12 col-md-8">
                                            <label class="form-label" for="name-create"><?= $translator->t('organization.functions.designation'); ?></label>
                                            <input type="text" name="name" id="name-create" class="form-control">
                                        </div>

                                        <div class="col-12 col-md-4 d-flex align-items-md-end">
                                            <label class="form-check mb-2 mb-md-0">
                                                <input class="form-check-input" type="checkbox" name="isActive" value="1" checked>
                                                <span class="form-check-label"><?= $translator->t('organization.functions.active'); ?></span>
                                            </label>
                                        </div>

                                        <div class="col-12 col-md-4 d-flex align-items-md-end">
                                            <label class="form-check mb-2 mb-md-0">
                                                <input class="form-check-input" type="checkbox" name="maySign" value="1" checked>
                                                <span class="form-check-label"><?= $translator->t('organization.functions.authToSign'); ?></span>
                                            </label>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label" for="description-create"><?= $translator->t('organization.functions.description'); ?></label>
                                            <textarea name="description" id="description-create" class="form-control" rows="4"></textarea>
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

            </div>
        </div>
    </div>

    <?php include_once 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js" defer></script>
</body>

</html>