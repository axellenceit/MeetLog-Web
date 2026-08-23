<?php

declare(strict_types=1);

namespace Src\Core;

use Src\Auth\AuthService;
use Src\User\UserRepository;
use Src\Organisation\OrganisationRepository;
use Src\Organisation\OrganisationsFunktionRepository;
use Src\Organisation\OrganisationsMitgliedRepository;
use Src\UI\FlashService;
use Src\UI\Translator;
use Src\UI\LanguageRepository;
use Src\Besprechung\BesprechungRepository;
use Src\Besprechung\BesprechungspunktRepository;
use Src\Besprechung\BesprechungsteilnehmerRepository;
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
    private OrganisationRepository $organisationRepository;
    private OrganisationsFunktionRepository $organisationsFunktionRepository;
    private OrganisationsMitgliedRepository $organisationsMitgliedRepository;
    private LanguageRepository $languageRepository;
    private BesprechungRepository $besprechungRepository;
    private BesprechungspunktRepository $besprechungspunktRepository;
    private BesprechungsteilnehmerRepository $besprechungsteilnehmerRepository;
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
        $this->organisationRepository = new OrganisationRepository($this->db);
        $this->mail = new Mail($this->config, $this->translator, $this->organisationRepository);
        $this->flash = new FlashService($this->session);
        $this->userRepository = new UserRepository($this->db);
        $this->auth = new AuthService($this->userRepository, $this->session, $this->translator);
        $this->redirector = new Redirector();
        $this->organisationsFunktionRepository = new OrganisationsFunktionRepository($this->db);
        $this->organisationsMitgliedRepository = new OrganisationsMitgliedRepository($this->db);
        $this->languageRepository = new LanguageRepository($this->db, $this->translator);
        $this->besprechungRepository = new BesprechungRepository($this->db);
        $this->besprechungspunktRepository = new BesprechungspunktRepository($this->db);
        $this->besprechungsteilnehmerRepository = new BesprechungsteilnehmerRepository($this->db);
        $this->personRepository = new PersonRepository($this->db);
        $this->countryRepository = new CountryRepository($this->db);
        $this->acl = new ACL($this->userRepository, $this->organisationsFunktionRepository, $this->organisationsMitgliedRepository);
        $this->taskRepository = new TaskRepository($this->db);
        $this->logRepository = new LogRepository($this->db);
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

    public function organisationRepository(): OrganisationRepository
    {
        return $this->organisationRepository;
    }

    public function organisationsMitgliedRepository(): OrganisationsMitgliedRepository
    {
        return $this->organisationsMitgliedRepository;
    }

    public function organisationsFunktionRepository(): OrganisationsFunktionRepository
    {
        return $this->organisationsFunktionRepository;
    }

    public function languageRepository(): LanguageRepository
    {
        return $this->languageRepository;
    }

    public function besprechungRepository(): BesprechungRepository
    {
        return $this->besprechungRepository;
    }

    public function besprechungspunktRepository(): BesprechungspunktRepository
    {
        return $this->besprechungspunktRepository;
    }

    public function besprechungsteilnehmerRepository(): BesprechungsteilnehmerRepository
    {
        return $this->besprechungsteilnehmerRepository;
    }

    public function personRepository(): PersonRepository
    {
        return $this->personRepository;
    }

    public function getLogo(): string
    {

        $user = $this->userRepository->findById((int) $this->session->get('id'));
        $config = $this->config();
        $organisation = $this->organisationRepository()->findById((int) $user->standardOrganisation);

        return (empty($organisation?->logo) ? $config->get('general', 'url') . 'img/meetlog_256.png' : $config->get('general', 'url') . $organisation->logo);
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
