<?php

namespace Src\UI;

use Src\Core\Session;

final class FlashService
{
    public function __construct(private Session $session) {}

    public function set(string $key, string $message): void
    {
        $this->session->set($key, $message);
    }

    public function get(string $key): string
    {
        $value = $this->session->get($key, '');
        $this->session->remove($key);
        return $value;
    }

    public function addToast(string $message, string $type = 'success'): void
    {
        $message = str_replace(["\r\n", '\r\n', '\n', '\r'], "\n", $message);
        $toasts = $this->session->get('toasts', []);
        $toasts[] = ['message' => $message, 'type' => $type];
        $this->session->set('toasts', $toasts);
    }

    public function getToasts(): array
    {
        $toasts = $this->session->get('toasts', []);
        $this->session->remove('toasts');
        return $toasts;
    }
}
