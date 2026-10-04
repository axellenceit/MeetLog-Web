<?php

declare(strict_types=1);

namespace Src\Meeting;

use Src\Core\Database;

final class MeetingAttachmentRepository
{
    public const CONTEXT_INVITATION = 'Invitation';
    public const CONTEXT_MINUTES = 'Minutes';

    public function __construct(private Database $db) {}

    public function findById(int $id): ?MeetingAttachment
    {
        $row = $this->db->first(
            'SELECT * FROM tblMeetingAttachment WHERE id = ? AND IsActive = 1 LIMIT 1',
            [$id]
        );

        return $row ? MeetingAttachment::fromArray($row) : null;
    }

    /**
     * @return MeetingAttachment[]
     */
    public function findByMeetingId(int $meetingId): array
    {
        $rows = $this->db->query(
            'SELECT * FROM tblMeetingAttachment WHERE Meeting = ? AND IsActive = 1 ORDER BY SortOrder ASC, id ASC',
            [$meetingId]
        );

        return array_map(
            static fn(array $row): MeetingAttachment => MeetingAttachment::fromArray($row),
            $rows
        );
    }

    /**
     * Anhänge, die bei Einladung bzw. Protokoll mit gedruckt werden sollen.
     *
     * @return MeetingAttachment[]
     */
    public function findPrintableByMeetingId(int $meetingId, string $context): array
    {
        $column = $context === self::CONTEXT_INVITATION ? 'PrintWithInvitation' : 'PrintWithMinutes';

        $rows = $this->db->query(
            "SELECT * FROM tblMeetingAttachment WHERE Meeting = ? AND IsActive = 1 AND {$column} = 'Y' ORDER BY SortOrder ASC, id ASC",
            [$meetingId]
        );

        return array_map(
            static fn(array $row): MeetingAttachment => MeetingAttachment::fromArray($row),
            $rows
        );
    }

    public function create(MeetingAttachment $attachment): int
    {
        $sql = 'INSERT INTO tblMeetingAttachment
            (Meeting, FileName, OriginalFileName, FilePath, MimeType, FileSize, Description, SortOrder, PrintWithInvitation, PrintWithMinutes, UploadedBy, IsActive)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $attachment->meeting,
            $attachment->fileName,
            $attachment->originalFileName,
            $attachment->filePath,
            $attachment->mimeType,
            $attachment->fileSize,
            $attachment->description,
            $attachment->sortOrder,
            $attachment->printWithInvitation ? 'Y' : 'N',
            $attachment->printWithMinutes ? 'Y' : 'N',
            $attachment->uploadedBy,
            $attachment->isActive ? 1 : 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(MeetingAttachment $attachment): bool
    {
        if ($attachment->id === null) {
            throw new \InvalidArgumentException('Anhang-ID darf nicht null sein.');
        }

        $sql = 'UPDATE tblMeetingAttachment SET
            OriginalFileName = ?,
            Description = ?,
            SortOrder = ?,
            PrintWithInvitation = ?,
            PrintWithMinutes = ?
            WHERE id = ? AND IsActive = 1';

        return $this->db->execute($sql, [
            $attachment->originalFileName,
            $attachment->description,
            $attachment->sortOrder,
            $attachment->printWithInvitation ? 'Y' : 'N',
            $attachment->printWithMinutes ? 'Y' : 'N',
            $attachment->id,
        ]);
    }

    public function updatePrintFlags(int $id, bool $withInvitation, bool $withMinutes): bool
    {
        return $this->db->execute(
            'UPDATE tblMeetingAttachment SET PrintWithInvitation = ?, PrintWithMinutes = ? WHERE id = ? AND IsActive = 1',
            [$withInvitation ? 'Y' : 'N', $withMinutes ? 'Y' : 'N', $id]
        );
    }

    /**
     * Soft-Delete: Datensatz bleibt erhalten, wird aber nicht mehr angezeigt.
     */
    public function delete(int $id): bool
    {
        return $this->db->execute(
            'UPDATE tblMeetingAttachment SET IsActive = 0 WHERE id = ?',
            [$id]
        );
    }
}
