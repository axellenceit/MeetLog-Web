<?php

declare(strict_types=1);

namespace Src\Organization;

use Src\Core\Database;
use Src\Log\LogRepository;
use Src\Log\Log;

final class OrganizationFunctionRepository
{
    public function __construct(
        private Database $db,
        private LogRepository $logRepository
    ) {}

    public function findById(int $id): ?OrganizationFunction
    {
        $sql = 'SELECT * FROM tblOrganizationFunction WHERE id = ? LIMIT 1';
        $rows = $this->db->query($sql, [$id]);

        if (empty($rows)) {
            return null;
        }

        return OrganizationFunction::fromArray($rows[0]);
    }

    /**
     * @return OrganizationFunction[]
     */
    public function findAllByOrganization(int $organizationId, bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM tblOrganizationFunction WHERE Organization = ?';
        if ($onlyActive) {
            $sql .= ' AND IsActive = 1';
        }
        $sql .= ' ORDER BY SortOrder ASC, Name ASC';

        $rows = $this->db->query($sql, [$organizationId]);

        return array_map(
            static fn(array $row): OrganizationFunction => OrganizationFunction::fromArray($row),
            $rows
        );
    }

    public function create(OrganizationFunction $function): int
    {

        try {

            $sql = "INSERT INTO tblOrganizationFunction
                (Organization, Name, Description, SortOrder, IsActive, MaySign, MayManageOrganization, MayManageMeetings)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)";

            $data = $function->toDatabaseArray();

            $this->db->execute($sql, [
                $data['Organization'],
                $data['Name'],
                $data['Description'],
                $data['SortOrder'],
                $data['IsActive'],
                $data['MaySign'],
                $data['MayManageOrganization'],
                $data['MayManageMeetings']
            ]);

            $lastInsertId = (int) $this->db->lastInsertId();

            $logEntry = new Log(
                id: null,
                userId: (int) $_SESSION['id'],
                action: 'Neue Organisationsfunktion ' . $data['Name'] . ' erstellt',
                severity: 'info',
                timestamp: date('Y-m-d H:i:s')
            );
            
            $this->logRepository->create($logEntry);

        } catch (\Exception $e) {
            // Handle exception if needed
        
            $logEntry = new Log(
                id: null,
                userId: (int) $_SESSION['id'],
                action: 'Fehler beim Erstellen der Organisationsfunktion: ' . $e->getMessage(),
                severity: 'error',
                timestamp: date('Y-m-d H:i:s')
            );

            $this->logRepository->create($logEntry);

            throw $e;
        }

        return $lastInsertId;
    }

    public function update(OrganizationFunction $function): bool
    {
        if ($function->id === null) {
            return false;
        }

        $data = $function->toDatabaseArray();

        $sql = "UPDATE tblOrganizationFunction SET
                Organization = ?,
                Name = ?,
                Description = ?,
                SortOrder = ?,
                IsActive = ?,
                MaySign = ?,
                MayManageOrganization = ?,
                MayManageMeetings = ?
             WHERE id = ?";

        $data['id'] = $function->id;

        return $this->db->execute($sql, [
            $data['Organization'],
            $data['Name'],
            $data['Description'],
            $data['SortOrder'],
            $data['IsActive'],
            $data['MaySign'],
            $data['MayManageOrganization'],
            $data['MayManageMeetings'],
            $data['id']
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM tblOrganizationFunction WHERE id = ?";

        return $this->db->execute($sql, [$id]);
    }
}
