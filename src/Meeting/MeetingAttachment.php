<?php

declare(strict_types=1);

namespace Src\Meeting;

final class MeetingAttachment
{
    public function __construct(
        public ?int $id = null,
        public ?int $meeting = null,
        public string $fileName = '',
        public string $originalFileName = '',
        public ?string $filePath = null,
        public ?string $mimeType = null,
        public ?int $fileSize = null,
        public ?string $description = null,
        public int $sortOrder = 0,
        public bool $printWithInvitation = false,
        public bool $printWithMinutes = false,
        public ?int $uploadedBy = null,
        public bool $isActive = true,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            meeting: isset($row['Meeting']) ? (int)$row['Meeting'] : null,
            fileName: (string)($row['FileName'] ?? ''),
            originalFileName: (string)($row['OriginalFileName'] ?? ''),
            filePath: $row['FilePath'] ?? null,
            mimeType: $row['MimeType'] ?? null,
            fileSize: isset($row['FileSize']) ? (int)$row['FileSize'] : null,
            description: $row['Description'] ?? null,
            sortOrder: isset($row['SortOrder']) ? (int)$row['SortOrder'] : 0,
            printWithInvitation: ($row['PrintWithInvitation'] ?? 'N') === 'Y',
            printWithMinutes: ($row['PrintWithMinutes'] ?? 'N') === 'Y',
            uploadedBy: isset($row['UploadedBy']) ? (int)$row['UploadedBy'] : null,
            isActive: (bool)($row['IsActive'] ?? true),
            createdAt: $row['CreatedAt'] ?? null,
            updatedAt: $row['UpdatedAt'] ?? null,
        );
    }

    public function toDatabaseArray(): array
    {
        return [
            'Meeting' => $this->meeting,
            'FileName' => $this->fileName,
            'OriginalFileName' => $this->originalFileName,
            'FilePath' => $this->filePath,
            'MimeType' => $this->mimeType,
            'FileSize' => $this->fileSize,
            'Description' => $this->description,
            'SortOrder' => $this->sortOrder,
            'PrintWithInvitation' => $this->printWithInvitation ? 'Y' : 'N',
            'PrintWithMinutes' => $this->printWithMinutes ? 'Y' : 'N',
            'UploadedBy' => $this->uploadedBy,
            'IsActive' => $this->isActive ? 1 : 0,
            'CreatedAt' => $this->createdAt,
            'UpdatedAt' => $this->updatedAt,
        ];
    }

    /**
     * Lesbar formatierte Dateigröße (z. B. "1,4 MB")
     */
    public function formattedSize(): string
    {
        if ($this->fileSize === null) {
            return '';
        }

        $sizes = ['B', 'KB', 'MB', 'GB'];
        $value = (float)$this->fileSize;
        $level = 0;

        while ($value >= 1024 && $level < count($sizes) - 1) {
            $value /= 1024;
            $level++;
        }

        return number_format($value, $level === 0 ? 0 : 1, ',', '.') . ' ' . $sizes[$level];
    }
}
