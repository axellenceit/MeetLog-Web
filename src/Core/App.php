<?php

declare(strict_types=1);

namespace Src\Core;

use Src\Auth\AuthService;
use Src\User\UserRepository;
use Src\Organization\OrganizationRepository;
use Src\Organization\OrganizationFunctionRepository;
use Src\Organization\OrganizationMemberRepository;
use Src\UI\FlashService;
use Src\UI\Translator;
use Src\UI\LanguageRepository;
use Src\Meeting\MeetingRepository;
use Src\Meeting\MeetingPointRepository;
use Src\Meeting\MeetingParticipantRepository;
use Src\Meeting\MeetingAttachmentRepository;
use Src\Core\CountryRepository;
use Src\Person\PersonRepository;
use Src\User\ACL;
use Src\Task\TaskRepository;
use Src\Log\LogRepository;

final class App
{

    private Config $config;
    private Session $session;
    private Database $db;
    private Mail $mail;
    private FlashService $flash;
    private UserRepository $userRepository;
    private AuthService $auth;
    private Redirector $redirector;
    private Translator $translator;
    private OrganizationRepository $organizationRepository;
    private OrganizationFunctionRepository $organizationFunctionRepository;
    private OrganizationMemberRepository $organizationMemberRepository;
    private LanguageRepository $languageRepository;
    private MeetingRepository $meetingRepository;
    private MeetingPointRepository $meetingPointRepository;
    private MeetingParticipantRepository $meetingParticipantRepository;
    private MeetingAttachmentRepository $meetingAttachmentRepository;
    private PersonRepository $personRepository;
    private CountryRepository $countryRepository;
    private ACL $acl;
    private TaskRepository $taskRepository;
    private LogRepository $logRepository;

    public function __construct()
    {
        date_default_timezone_set('Europe/Berlin');

        $this->config = new Config(dirname(__DIR__, 1) . '/../static/meetlog.ini');

        $this->session = new Session();
        $this->session->start();

        $this->db = new Database($this->config);
        $this->translator = new Translator();
        $this->organizationRepository = new OrganizationRepository($this->db);
        $this->mail = new Mail($this->config, $this->translator, $this->organizationRepository);
        $this->flash = new FlashService($this->session);
        $this->userRepository = new UserRepository($this->db);
        $this->auth = new AuthService($this->userRepository, $this->session, $this->translator);
        $this->redirector = new Redirector();
        $this->organizationFunctionRepository = new OrganizationFunctionRepository($this->db);
        $this->organizationMemberRepository = new OrganizationMemberRepository($this->db);
        $this->languageRepository = new LanguageRepository($this->db, $this->translator);
        $this->meetingRepository = new MeetingRepository($this->db);
        $this->meetingPointRepository = new MeetingPointRepository($this->db);
        $this->meetingParticipantRepository = new MeetingParticipantRepository($this->db);
        $this->meetingAttachmentRepository = new MeetingAttachmentRepository($this->db);
        $this->personRepository = new PersonRepository($this->db);
        $this->countryRepository = new CountryRepository($this->db);
        $this->acl = new ACL($this->userRepository, $this->organizationFunctionRepository, $this->organizationMemberRepository);
        $this->taskRepository = new TaskRepository($this->db);
        $this->logRepository = new LogRepository($this->db);

        $this->meetingRepository->finishPastMeetings();
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function session(): Session
    {
        return $this->session;
    }

    public function db(): Database
    {
        return $this->db;
    }

    public function mail(): Mail
    {
        return $this->mail;
    }

    public function flash(): FlashService
    {
        return $this->flash;
    }

    public function userRepository(): UserRepository
    {
        return $this->userRepository;
    }

    public function auth(): AuthService
    {
        return $this->auth;
    }

    public function redirector(): Redirector
    {
        return $this->redirector;
    }

    public function translator(): Translator
    {
        return $this->translator;
    }

    public function organizationRepository(): OrganizationRepository
    {
        return $this->organizationRepository;
    }

    public function organizationMemberRepository(): OrganizationMemberRepository
    {
        return $this->organizationMemberRepository;
    }

    public function organizationFunctionRepository(): OrganizationFunctionRepository
    {
        return $this->organizationFunctionRepository;
    }

    public function languageRepository(): LanguageRepository
    {
        return $this->languageRepository;
    }

    public function meetingRepository(): MeetingRepository
    {
        return $this->meetingRepository;
    }

    public function meetingPointRepository(): MeetingPointRepository
    {
        return $this->meetingPointRepository;
    }

    public function meetingParticipantRepository(): MeetingParticipantRepository
    {
        return $this->meetingParticipantRepository;
    }

    public function meetingAttachmentRepository(): MeetingAttachmentRepository
    {
        return $this->meetingAttachmentRepository;
    }

    public function personRepository(): PersonRepository
    {
        return $this->personRepository;
    }

    public function getLogo(): string
    {

        $user = $this->userRepository->findById((int) $this->session->get('id'));
        $config = $this->config();
        $organization = $this->organizationRepository()->findById((int) $user->defaultOrganization);

        return (empty($organization?->logo) ? $config->get('general', 'url') . 'img/meetlog_256.png' : $config->get('general', 'url') . $organization->logo);
    }

    public function countryRepository(): CountryRepository
    {
        return $this->countryRepository;
    }

    public function acl(): ACL
    {
        return $this->acl;
    }

    public function taskRepository(): TaskRepository
    {
        return $this->taskRepository;
    }

    public function logRepository(): LogRepository
    {
        return $this->logRepository;
    }
}
