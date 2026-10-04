<?php

declare(strict_types=1);

namespace Src\UI;

use Src\Core\Database;
use Src\UI\Translator;

final class LanguageRepository
{

    public function __construct(private Database $db, private Translator $translator) {}

    public function findActiveForSelector(): array
    {

        $availableLanguages = $this->translator->getSupportedLanguages();

        $sql = "SELECT IsoCode2, LocaleCode, LanguageNameNative, FlagEmoji, SortOrder
                FROM tblLanguage
                WHERE IsActive = 1
                ORDER BY SortOrder ASC, LanguageNameNative ASC";

        $languages = $this->db->query($sql);

        return array_values(array_filter(
            $languages,
            fn(array $language): bool => in_array($language['IsoCode2'], $availableLanguages, true)
        ));
    }
}
