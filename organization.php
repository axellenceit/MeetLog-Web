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
    $redirect->to('index.php');
}

$organization = $organizationRepository->findById($organizationId);

if ($organization === null) {
    $redirect->to('index.php');
}

$organizationError = (string) ($flash->get('organization_error') ?? '');
$organizationSuccess = (string) ($flash->get('organization_success') ?? '');

$countries = $countryRepository->findAll();
?>
<!DOCTYPE html>
<html lang="de">

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

        <div class="page-wrapper">
            <div class="container-xl py-3">
                <ul class="nav nav-bordered mb-4 flex-nowrap overflow-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="./organization.php">
                            <?= htmlspecialchars($translator->t('organization.overview'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./organization.functions.php">
                            <?= htmlspecialchars($translator->t('organization.functions.root'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                </ul>

                <form action="actions.php?action=updateOrganization" method="post" enctype="multipart/form-data">
                    <input type="hidden" id="organizationId" name="organizationId" value="<?= $organization->id; ?>">
                    <div class="card">

                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-12">
                                    <h4 class="mb-3"><?= htmlspecialchars($translator->t('organization.organization'), ENT_QUOTES, 'UTF-8') ?></h4>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="name1" class="form-label">
                                        <?= htmlspecialchars($translator->t('organization.logo'), ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        type="file"
                                        name="logo"
                                        id="logo"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($organization?->logo ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="name1" class="form-label">
                                        <?= htmlspecialchars($translator->t('organization.name1'), ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        type="text"
                                        name="name1"
                                        id="name1"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($organization?->name1 ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="name2" class="form-label">
                                        <?= htmlspecialchars($translator->t('organization.name2'), ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        type="text"
                                        name="name2"
                                        id="name2"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($organization?->name2 ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="registryCourt" class="form-label">
                                        <?= htmlspecialchars($translator->t('organization.localcourt'), ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        type="text"
                                        name="registryCourt"
                                        id="registryCourt"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($organization?->registryCourt ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="registryEntry" class="form-label">
                                        <?= htmlspecialchars($translator->t('organization.registration'), ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        type="text"
                                        name="registryEntry"
                                        id="registryEntry"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($organization?->registryEntry ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12">
                                    <hr class="my-1">
                                    <h4 class="mb-3"><?= htmlspecialchars($translator->t('organization.address.address'), ENT_QUOTES, 'UTF-8') ?></h4>
                                </div>

                                <div class="col-12">
                                    <label for="street" class="form-label">
                                        <?= htmlspecialchars($translator->t('organization.address.street'), ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        type="text"
                                        name="street"
                                        id="street"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($organization?->street ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-5">
                                    <label for="country" class="form-label">
                                        <?= htmlspecialchars($translator->t('organization.address.country'), ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <select class="form-select" name="country" id="country">
                                        <option value="">--Select--</option>
                                        <?php foreach ($countries as $country): ?>
                                            <option value="<?= htmlspecialchars($country->isoAlpha2, ENT_QUOTES, 'UTF-8') ?>" <?= ($organization?->country === $country->isoAlpha2) ? 'selected' : '' ?>>
                                                <?php if (strtoupper($currentLang) === 'DE'): ?>
                                                    <?= htmlspecialchars($country->nameDe, ENT_QUOTES, 'UTF-8') ?>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($country->nameEn, ENT_QUOTES, 'UTF-8') ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-3">
                                    <label for="zipCode" class="form-label">
                                        <?= htmlspecialchars($translator->t('organization.address.zip'), ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        type="text"
                                        name="zipCode"
                                        id="zipCode"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($organization?->zipCode ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="city" class="form-label">
                                        <?= htmlspecialchars($translator->t('organization.address.city'), ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        type="text"
                                        name="city"
                                        id="city"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($organization?->city ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12">
                                    <hr class="my-1">
                                    <h4 class="mb-3">Kommunikation</h4>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="phone" class="form-label">
                                        <?= htmlspecialchars($translator->t('organization.contact.phone'), ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        type="tel"
                                        name="phone"
                                        id="phone"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($organization?->phone ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="fax" class="form-label">
                                        <?= htmlspecialchars($translator->t('organization.contact.facsimile'), ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        type="tel"
                                        name="fax"
                                        id="fax"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($organization?->fax ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="email" class="form-label">
                                        <?= htmlspecialchars($translator->t('organization.contact.mail'), ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        type="email"
                                        name="email"
                                        id="email"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($organization?->email ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="homepage" class="form-label">
                                        <?= htmlspecialchars($translator->t('organization.contact.web'), ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        type="url"
                                        name="homepage"
                                        id="homepage"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($organization?->homepage ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-transparent">
                            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                                <a href="<?= htmlspecialchars($currentURL, ENT_QUOTES, 'UTF-8') ?>" class="btn">
                                    <?= htmlspecialchars($translator->t('common.cancel'), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <?= htmlspecialchars($translator->t('common.save'), ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include_once 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js" defer></script>
</body>

</html>