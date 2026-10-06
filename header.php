<?php

declare(strict_types=1);

use Src\User\Permission;

$currentURL = basename($_SERVER['SCRIPT_NAME']);
$currentLang = $translator->getLocale();

$languages = $languageRepository->findActiveForSelector();
$user = $userRepository->findById($session->get('id'));

$fullName = trim(
    (string)($session->get('FirstName') ?? '') . ' ' . (string)($session->get('LastName') ?? '')
);

$avatarStyle = '';
if (!empty($user->photo)) {
    $avatarStyle = 'background-image: url(' . htmlspecialchars((string)$user->photo, ENT_QUOTES, 'UTF-8') . ');';
}

$logRepository->logRotation((int)$config->get('general', 'logRotation'));

$authToSign = $user->isAdmin || $acl->isPermitted($user->id, (int)$session->get('Organization'), Permission::SIGN);
$authToManageOrganization = $user->isAdmin || $acl->isPermitted($user->id, (int)$session->get('Organization'), Permission::MANAGE_ORGANIZATION);
$authToManageMeetings = $user->isAdmin || $acl->isPermitted($user->id, (int)$session->get('Organization'), Permission::MANAGE_MEETINGS);

function isActive(array $files, string $currentURL): string
{
    return in_array($currentURL, $files, true) ? 'active' : '';
}
?>

<header class="navbar navbar-expand-lg d-print-none border-bottom bg-white">
    <div class="container-xl">
        <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"
            aria-controls="navbar-menu" aria-expanded="false" aria-label="Navigation umschalten">
            <span class="navbar-toggler-icon"></span>
        </button>

        <a href="./" class="navbar-brand navbar-brand-autodark me-3 pe-0 pe-md-3" aria-label="MeetLog">
            <img src="<?= $app->getLogo(); ?>" alt="MeetLog" class="navbar-brand-image" style="max-height: 56px; width: auto;">
        </a>

        <div class="collapse navbar-collapse" id="navbar-menu">
            <div class="d-flex flex-column flex-lg-row flex-fill align-items-stretch align-items-lg-center gap-3">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item <?= isActive(['index.php'], $currentURL); ?>">
                        <a class="nav-link" href="./">
                            <span class="nav-link-icon d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-1" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12l-2 0l9 -9l9 9l-2 0"></path>
                                    <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"></path>
                                    <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"></path>
                                </svg>
                            </span>
                            <span class="nav-link-title"><?= htmlspecialchars($translator->t('nav.home'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    </li>

                    <li class="nav-item <?= isActive(['meetings.php', 'meeting.php'], $currentURL); ?>">
                        <a class="nav-link" href="./meetings.php">
                            <span class="nav-link-icon d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar-stats"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M11.795 21h-6.795a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v4" />
                                    <path d="M18 14v4h4" />
                                    <path d="M14 18a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                                    <path d="M15 3v4" />
                                    <path d="M7 3v4" />
                                    <path d="M3 11h16" />
                                </svg>
                            </span>
                            <span class="nav-link-title"><?= htmlspecialchars($translator->t('nav.meetings'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    </li>

                    <li class="nav-item <?= isActive(['tasks.php'], $currentURL); ?>">
                        <a class="nav-link" href="./tasks.php">
                            <span class="nav-link-icon d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-list-details">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M13 5h8" />
                                    <path d="M13 9h5" />
                                    <path d="M13 15h8" />
                                    <path d="M13 19h5" />
                                    <path d="M3 5a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -4" />
                                    <path d="M3 15a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -4" />
                                </svg>
                            </span>
                            <span class="nav-link-title"><?= htmlspecialchars($translator->t('nav.tasks'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    </li>

                    <?php if ($authToManageOrganization): ?>
                        <li class="nav-item <?= isActive(['organization.php', 'organization.mitglieder.php', 'organization.functions.php'], $currentURL); ?>">
                            <a class="nav-link" href="./organization.php">
                                <span class="nav-link-icon d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sitemap"
                                        width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M3 17a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2l0 -2" />
                                        <path d="M15 17a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2l0 -2" />
                                        <path d="M9 5a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2l0 -2" />
                                        <path d="M6 15v-1a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v1" />
                                        <path d="M12 9l0 3" />
                                    </svg>
                                </span>
                                <span class="nav-link-title"><?= htmlspecialchars($translator->t('nav.organization'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        </li>

                        <li class="nav-item <?= isActive(['persons.php'], $currentURL); ?>">
                            <a class="nav-link" href="./persons.php">
                                <span class="nav-link-icon d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-users">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M5 7a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                                        <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                        <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                                    </svg>
                                </span>
                                <span class="nav-link-title"><?= htmlspecialchars($translator->t('nav.persons', [], 'Personen'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($user->isAdmin): ?>
                        <li class="nav-item <?= isActive(['einstellungen.php'], $currentURL); ?>">
                            <a class="nav-link" href="einstellungen.php"
                                aria-controls="offcanvasSettings">
                                <span class="nav-link-icon d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-1" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"></path>
                                        <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                                    </svg>
                                </span>
                                <span class="nav-link-title"><?= htmlspecialchars($translator->t('nav.settings'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        </li>

                        <li class="nav-item <?= isActive(['logs.php'], $currentURL); ?>">
                            <a class="nav-link" href="logs.php">
                                <span class="nav-link-icon d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-logs">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M4 12h.01" />
                                        <path d="M4 6h.01" />
                                        <path d="M4 18h.01" />
                                        <path d="M8 18h2" />
                                        <path d="M8 12h2" />
                                        <path d="M8 6h2" />
                                        <path d="M14 6h6" />
                                        <path d="M14 12h6" />
                                        <path d="M14 18h6" />
                                    </svg>
                                </span>
                                <span class="nav-link-title"><?= htmlspecialchars($translator->t('nav.logs'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 ms-lg-3">
                    <div class="w-100 w-lg-auto">
                        <label for="lang-select" class="visually-hidden">Sprache</label>
                        <select name="lang" id="lang-select" class="form-select" onchange="changeLanguage(this.value)">
                            <?php foreach ($languages as $lang): ?>
                                <?php $selected = (($lang['IsoCode2'] ?? '') === $currentLang) ? 'selected' : ''; ?>
                                <option value="<?= htmlspecialchars((string)$lang['IsoCode2'], ENT_QUOTES, 'UTF-8'); ?>" <?= $selected; ?>>
                                    <?= htmlspecialchars((string)$lang['FlagEmoji'] . ' ' . (string)$lang['LanguageNameNative'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="nav-item dropdown">
                        <button type="button"
                            class="nav-link d-flex align-items-center text-reset px-2 py-2 border-0 bg-transparent"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Benutzermenü öffnen">
                            <span class="avatar avatar-sm me-2 rounded-circle flex-shrink-0" style="<?= $avatarStyle; ?>">
                                <?= empty($user->photo) ? htmlspecialchars((string)$user->avatar, ENT_QUOTES, 'UTF-8') : ''; ?>
                            </span>
                            <span class="d-block text-start">
                                <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="./profile.php" class="dropdown-item">
                                <?= htmlspecialchars($translator->t('nav.profile'), ENT_QUOTES, 'UTF-8'); ?>
                            </a>

                            <div class="dropdown-divider"></div>

                            <a href="./actions.php?action=logout" class="dropdown-item">
                                <?= htmlspecialchars($translator->t('auth.sign_out'), ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function changeLanguage(lang) {
        const url = new URL(window.location.href);
        url.searchParams.set('lang', lang);
        window.location.href = url.toString();
    }
</script>