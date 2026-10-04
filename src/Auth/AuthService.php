<?php

declare(strict_types=1);

namespace Src\Auth;

use Src\User\UserRepository;
use Src\Core\Session;
use Src\UI\Translator;

final class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private Session $session,
        private Translator $translator

    ) {}

    public function login(string $mail, string $password): ?array
    {

        if ($mail == '' || $password == '') {
            return null;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $user = $this->userRepository->findActiveByEmail($mail);

        if ($user === null) {
            return null;
        }

        $passwordCheck = password_verify($password, $hashedPassword);

        if (!$passwordCheck) {
            return null;
        }

        $this->session->set('id', $user->id);
        $this->session->set('user', $user);
        $this->session->set('FirstName', $user->firstName ?? '');
        $this->session->set('LastName', $user->lastName ?? '');
        $this->session->set('Mail', $user->mail ?? '');
        $this->session->set('DefaultOrganization', (int)($user->defaultOrganization ?? 0));
        $this->session->set('IsAdmin', (int)($user->isAdmin ?? 0));
        $this->session->set('lang', $user->lang ?? 'de');
        $this->translator->setLocale($this->session->get('lang'));

        return $user->toDatabaseArray();
    }

    public function logout(): void
    {
        $this->session->destroy();
    }

    public function isLoggedIn(): bool
    {
        return $this->session->get('id') !== null;
    }

    public function user(): ?array
    {
        $user = $this->session->get('user');
        return is_array($user) ? $user : null;
    }

    public function userId(): int|string|null
    {
        return $this->session->get('id');
    }

    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
