<?php

declare(strict_types=1);

namespace Johncms\EditorUploads;

use DateTimeImmutable;

interface EditorUploadRepositoryInterface
{
    /** Hold the account row until the transaction ends: the uploads of one user go one at a time. */
    public function lockUser(int $userId): void;

    /** Uploads of the user not attached to anything yet, the reserved places included. */
    public function countUnbound(int $userId): int;

    /** Reserve a place for a file being written; the id of the reservation. */
    public function reserve(int $userId, string $directory, DateTimeImmutable $now): int;

    public function fillReservation(int $id, int $fileId): void;

    public function delete(int $id): void;

    /**
     * Files the user uploaded that no record holds yet.
     *
     * @return list<int>
     */
    public function getUnboundFileIds(int $userId): array;

    /**
     * Files the record holds.
     *
     * @return list<int>
     */
    public function getOwnedFileIds(string $ownerType, int $ownerId): array;

    /**
     * Give the unattached uploads of the user to the record. Only the rows still unattached and
     * still the user's are changed; the number changed tells the caller whether all of them were.
     *
     * @param list<int> $fileIds
     */
    public function bind(array $fileIds, string $ownerType, int $ownerId, int $userId): int;

    /**
     * Remove the rows of the given files of the record.
     *
     * @param list<int> $fileIds
     */
    public function releaseFiles(string $ownerType, int $ownerId, array $fileIds): void;

    /**
     * Remove the rows of the records; the files they held.
     *
     * @param list<int> $ownerIds
     * @return list<int>
     */
    public function releaseOwners(string $ownerType, array $ownerIds): array;

    /**
     * Remove the unattached uploads of the user; their files.
     *
     * @return list<int>
     */
    public function releaseUnboundOfUser(int $userId): array;

    /**
     * Lock and remove the unattached rows older than the threshold, reservations included; the
     * files they had.
     *
     * @param list<string> $directories
     * @return list<int>
     */
    public function releaseAbandoned(array $directories, DateTimeImmutable $threshold, int $limit): array;

    /**
     * Files of the directories, registered before the threshold, that no row of the registry
     * knows.
     *
     * @param list<string> $directories
     * @return list<int>
     */
    public function getUntrackedFileIds(array $directories, DateTimeImmutable $threshold, int $limit): array;
}
