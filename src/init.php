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
$organizationRepository = $app->organizationRepository();
$organizationMemberRepository = $app->organizationMemberRepository();
$organizationFunctionRepository = $app->organizationFunctionRepository();
$languageRepository = $app->languageRepository();
$meetingRepository = $app->meetingRepository();
$meetingPointRepository = $app->meetingPointRepository();
$meetingParticipantRepository = $app->meetingParticipantRepository();
$meetingAttachmentRepository = $app->meetingAttachmentRepository();
$personRepository = $app->personRepository();
$countryRepository = $app->countryRepository();
$taskRepository = $app->taskRepository();
$acl = $app->acl();
$logRepository = $app->logRepository();
