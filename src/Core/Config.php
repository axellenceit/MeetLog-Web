<?php

declare(strict_types=1);

namespace Src\Core;

final class Config
{
    private array $data;
    private string $file;

    public function __construct(string $file)
    {
        if (!is_file($file)) {
            throw new \RuntimeException("Config file not found: {$file}");
        }

        $data = parse_ini_file($file, true);

        if ($data === false) {
            throw new \RuntimeException("Config file could not be parsed: {$file}");
        }

        $this->file = $file;
        $this->data = $data;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function section(string $section): array
    {
        return $this->data[$section] ?? [];
    }

    public function get(string $section, string $key, mixed $default = null): mixed
    {
        return $this->data[$section][$key] ?? $default;
    }

    public function set(string $section, string $key, string $value): bool
    {
        if (!isset($this->data[$section])) {
            $this->data[$section] = [];
        }

        $this->data[$section][$key] = $value;

        return true;
    }

    public function has(string $section, string $key): bool
    {
        return array_key_exists($section, $this->data)
            && array_key_exists($key, $this->data[$section]);
    }

    public function save(): bool
    {
        $content = $this->buildIniString($this->data);
        return file_put_contents($this->file, $content) !== false;
    }

    private function buildIniString(array $data): string
    {
        $lines = [];

        foreach ($data as $section => $values) {
            $lines[] = "[{$section}]";

            foreach ($values as $key => $value) {
                $lines[] = $key . " = " . $this->encodeIniValue($value);
            }

            $lines[] = "";
        }

        return implode(PHP_EOL, $lines);
    }

    private function encodeIniValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        return '"' . addcslashes((string)$value, '"') . '"';
    }

    public function reload(): void
    {
        $this->data = parse_ini_file($this->file, true);
    }
}
