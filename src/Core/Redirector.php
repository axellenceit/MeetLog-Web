<?php
declare(strict_types=1);

namespace Src\Core;

final class Redirector
{
    public function to(string $url, int $statusCode = 302): never
    {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }

    public function back(): never
    {
        $target = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->to($target);
    }
}