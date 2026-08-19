<?php

declare(strict_types=1);

namespace Src\UI;

final class Translator
{
    private string $locale;
    private string $fallbackLocale;
    private string $langPath;
    private array $translations = [];
    private array $supportedLanguages = [];

    public function __construct(
        string $locale = 'de',
        ?string $langPath = null,
        string $fallbackLocale = 'de'
    ) {
        $this->langPath = $langPath ?? dirname(__DIR__, 2) . '/lang';
        $this->supportedLanguages = $this->detectSupportedLanguages();
        $this->fallbackLocale = $fallbackLocale;
        $this->locale = in_array($locale, $this->supportedLanguages, true) ? $locale : $fallbackLocale;
        $this->load();
    }

    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    public function setLocale(string $locale): void
    {
        if (!in_array($locale, $this->supportedLanguages, true)) {
            return;
        }

        $this->locale = $locale;
        $this->load();
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function translate(string $key, array $replace = [], ?string $fallback = null): string
    {
        $value = $this->getByDotKey($this->translations, $key);

        if (!is_string($value)) {
            return $fallback ?? $key;
        }

        return $this->replacePlaceholders($value, $replace);
    }

    public function t(string $key, array $replace = [], ?string $fallback = null): string
    {
        return $this->translate($key, $replace, $fallback);
    }

    private function load(): void
    {
        $this->translations = $this->loadLocaleCatalog($this->locale);

        if ($this->locale !== $this->fallbackLocale) {
            $fallbackTranslations = $this->loadLocaleCatalog($this->fallbackLocale);
            $this->translations = $this->mergeRecursiveDistinct($fallbackTranslations, $this->translations);
        }
    }

    private function loadLocaleCatalog(string $locale): array
    {
        $dir = rtrim($this->langPath, '/\\') . DIRECTORY_SEPARATOR . $locale;

        if (!is_dir($dir)) {
            return [];
        }

        $catalog = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $segments = explode(DIRECTORY_SEPARATOR, $relativePath);
            $segments[count($segments) - 1] = pathinfo($segments[count($segments) - 1], PATHINFO_FILENAME);

            $data = require $file->getPathname();

            if (!is_array($data)) {
                continue;
            }

            $catalog = $this->setNested($catalog, $segments, $data);
        }

        return $catalog;
    }

    private function detectSupportedLanguages(): array
    {
        if (!is_dir($this->langPath)) {
            return [];
        }

        $entries = scandir($this->langPath);
        if ($entries === false) {
            return [];
        }

        $languages = [];

        foreach ($entries as $entry) {
            $fullPath = $this->langPath . DIRECTORY_SEPARATOR . $entry;
            if ($entry !== '.' && $entry !== '..' && is_dir($fullPath)) {
                $languages[] = $entry;
            }
        }

        sort($languages);

        return array_values(array_unique($languages));
    }

    private function getByDotKey(array $data, string $key): mixed
    {
        $segments = explode('.', $key);
        $value = $data;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    private function replacePlaceholders(string $text, array $replace): string
    {
        $map = [];

        foreach ($replace as $key => $value) {
            $map['{{' . $key . '}}'] = (string) $value;
            $map['{' . $key . '}'] = (string) $value;
        }

        return strtr($text, $map);
    }

    private function setNested(array $target, array $segments, array $value): array
    {
        $ref = &$target;

        foreach ($segments as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }

        $ref = $this->mergeRecursiveDistinct($ref, $value);

        return $target;
    }

    private function mergeRecursiveDistinct(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeRecursiveDistinct($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
