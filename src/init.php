<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Src\Core\App;

$app = new App();

$config = $app->config();
$session = $app->session();
$db = $app->db();
$mail = $app->mail();
$flash = $app->flash();
$userRepository = $app->userRepository();
$auth = $app->auth();
$redirect = $app->redirector();
$translator = $app->translator();
$organisationRepository = $app->organisationRepository();
$organisationsMitgliedRepository = $app->organisationsMitgliedRepository();
$organisationsFunktionRepository = $app->organisationsFunktionRepository();
$languageRepository = $app->languageRepository();
$besprechungRepository = $app->besprechungRepository();
$besprechungspunktRepository = $app->besprechungspunktRepository();
$besprechungsteilnehmerRepository = $app->besprechungsteilnehmerRepository();
$personRepository = $app->personRepository();
$countryRepository = $app->countryRepository();
$acl = $app->acl();
