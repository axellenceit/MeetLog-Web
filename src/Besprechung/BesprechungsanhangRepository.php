<?php

declare(strict_types=1);

namespace Src\Besprechung;

use Src\Core\Database;

final class BesprechungsanhangRepository
{
    public function __construct(private Database $db) {}

    public function findById(int $id): ?Besprechungsanhang
    {
        $row = $this->db->first(
            'SELECT * FROM tblBesprechungsanhang WHERE id = ? AND istAktiviert = 1 LIMIT 1',
            [$id]
        );

        return $row ? Besprechungsanhang::fromArray($row) : null;
    }

    /**
     * @return Besprechungsanhang[]
     */
    public function findByBesprechungId(int $besprechungId): array
    {
        $rows = $this->db->query(
            'SELECT * FROM tblBesprechungsanhang WHERE Besprechung = ? AND istAktiviert = 1 ORDER BY Sortierung ASC, id ASC',
            [$besprechungId]
        );

        return array_map(
            static fn(array $row): Besprechungsanhang => Besprechungsanhang::fromArray($row),
            $rows
        );
    }

    /**
     * Anhänge, die bei Einladung bzw. Protokoll mit gedruckt werden sollen.
     *
     * @return Besprechungsanhang[]
     */
    public function findPrintableByBesprechungId(int $besprechungId, string $kontext): array
    {
        $spalte = $kontext === 'Einladung' ? 'DruckenBeiEinladung' : 'DruckenBeiProtokoll';

        $rows = $this->db->query(
            "SELECT * FROM tblBesprechungsanhang WHERE Besprechung = ? AND istAktiviert = 1 AND {$spalte} = 'Y' ORDER BY Sortierung ASC, id ASC",
            [$besprechungId]
        );

        return array_map(
            static fn(array $row): Besprechungsanhang => Besprechungsanhang::fromArray($row),
            $rows
        );
    }

    public function create(Besprechungsanhang $anhang): int
    {
        $sql = 'INSERT INTO tblBesprechungsanhang
            (Besprechung, Dateiname, OriginalDateiname, Dateipfad, MimeTyp, Dateigroesse, Beschreibung, Sortierung, DruckenBeiEinladung, DruckenBeiProtokoll, HochgeladenVon, istAktiviert)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $anhang->besprechung,
            $anhang->dateiname,
            $anhang->originalDateiname,
            $anhang->dateipfad,
            $anhang->mimeTyp,
            $anhang->dateigroesse,
            $anhang->beschreibung,
            $anhang->sortierung,
            $anhang->druckenBeiEinladung ? 'Y' : 'N',
            $anhang->druckenBeiProtokoll ? 'Y' : 'N',
            $anhang->hochgeladenVon,
            $anhang->istAktiviert ? 1 : 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(Besprechungsanhang $anhang): bool
    {
        if ($anhang->id === null) {
            throw new \InvalidArgumentException('Anhang-ID darf nicht null sein.');
        }

        $sql = 'UPDATE tblBesprechungsanhang SET
            OriginalDateiname = ?,
            Beschreibung = ?,
            Sortierung = ?,
            DruckenBeiEinladung = ?,
            DruckenBeiProtokoll = ?
            WHERE id = ? AND istAktiviert = 1';

        return $this->db->execute($sql, [
            $anhang->originalDateiname,
            $anhang->beschreibung,
            $anhang->sortierung,
            $anhang->druckenBeiEinladung ? 'Y' : 'N',
            $anhang->druckenBeiProtokoll ? 'Y' : 'N',
            $anhang->id,
        ]);
    }

    public function updateDruckFlags(int $id, bool $beiEinladung, bool $beiProtokoll): bool
    {
        return $this->db->execute(
            'UPDATE tblBesprechungsanhang SET DruckenBeiEinladung = ?, DruckenBeiProtokoll = ? WHERE id = ? AND istAktiviert = 1',
            [$beiEinladung ? 'Y' : 'N', $beiProtokoll ? 'Y' : 'N', $id]
        );
    }

    /**
     * Soft-Delete: Datensatz bleibt erhalten, wird aber nicht mehr angezeigt.
     */
    public function delete(int $id): bool
    {
        return $this->db->execute(
            'UPDATE tblBesprechungsanhang SET istAktiviert = 0 WHERE id = ?',
            [$id]
        );
    }
}
