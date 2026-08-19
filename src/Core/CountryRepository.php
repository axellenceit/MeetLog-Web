<?php

declare(strict_types=1);

namespace Src\Core;

use Src\Core\Database;

final class CountryRepository
{
    public function __construct(private Database $db) {}

    public function findById(int $id): ?Country
    {
        $sql = "SELECT * FROM tblCountries WHERE countryId = ? LIMIT 1";
        $row = $this->db->first($sql, [$id]);

        return $row ? Country::fromArray($row) : null;
    }

    public function findAll(bool $onlyActive = false): array
    {
        $sql = "SELECT * FROM tblCountries";
        $params = [];

        if ($onlyActive) {
            $sql .= " WHERE istAktiviert = ?";
            $params[] = 1;
        }

        $sql .= " ORDER BY nameDE ASC";

        $rows = $this->db->query($sql, $params);

        return array_map(
            static fn(array $row): Country => Country::fromArray($row),
            $rows
        );
    }

    
}
