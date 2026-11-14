<?php

declare(strict_types=1);

namespace Johncms\EditorUploads\Infrastructure;

use DateTimeImmutable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Johncms\EditorUploads\EditorUpload;
use Johncms\EditorUploads\EditorUploadRepositoryInterface;

final readonly class EloquentEditorUploadRepository implements EditorUploadRepositoryInterface
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    public function lockUser(int $userId): void
    {
        Capsule::table('users')->where('id', $userId)->lockForUpdate()->value('id');
    }

    public function countUnbound(int $userId): int
    {
        return EditorUpload::query()->where('user_id', $userId)->whereNull('owner_type')->count();
    }

    public function reserve(int $userId, string $directory, DateTimeImmutable $now): int
    {
        return EditorUpload::query()->create([
            'user_id'    => $userId,
            'directory'  => $directory,
            'created_at' => $now->format(self::DATE_FORMAT),
        ])->id;
    }

    public function fillReservation(int $id, int $fileId): void
    {
        EditorUpload::query()->where('id', $id)->update(['file_id' => $fileId]);
    }

    public function delete(int $id): void
    {
        EditorUpload::query()->where('id', $id)->delete();
    }

    public function getUnboundFileIds(int $userId): array
    {
        return $this->ids(
            EditorUpload::query()->where('user_id', $userId)->whereNull('owner_type')->whereNotNull('file_id')->pluck('file_id')->all()
        );
    }

    public function getOwnedFileIds(string $ownerType, int $ownerId): array
    {
        return $this->ids(
            EditorUpload::query()->where('owner_type', $ownerType)->where('owner_id', $ownerId)->pluck('file_id')->all()
        );
    }

    public function bind(array $fileIds, string $ownerType, int $ownerId, int $userId): int
    {
        if ($fileIds === []) {
            return 0;
        }

        return EditorUpload::query()
            ->whereIn('file_id', $fileIds)
            ->whereNull('owner_type')
            ->where('user_id', $userId)
            ->update(['owner_type' => $ownerType, 'owner_id' => $ownerId]);
    }

    public function releaseFiles(string $ownerType, int $ownerId, array $fileIds): void
    {
        if ($fileIds === []) {
            return;
        }

        EditorUpload::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->whereIn('file_id', $fileIds)
            ->delete();
    }

    public function releaseOwners(string $ownerType, array $ownerIds): array
    {
        if ($ownerIds === []) {
            return [];
        }

        $query = static fn() => EditorUpload::query()->where('owner_type', $ownerType)->whereIn('owner_id', $ownerIds);
        $fileIds = $this->ids($query()->lockForUpdate()->pluck('file_id')->all());
        $query()->delete();

        return $fileIds;
    }

    public function releaseUnboundOfUser(int $userId): array
    {
        $query = static fn() => EditorUpload::query()->where('user_id', $userId)->whereNull('owner_type');
        $fileIds = $this->ids($query()->lockForUpdate()->pluck('file_id')->all());
        $query()->delete();

        return $fileIds;
    }

    public function releaseAbandoned(array $directories, DateTimeImmutable $threshold, int $limit): array
    {
        if ($directories === []) {
            return [];
        }

        $rows = EditorUpload::query()
            ->whereNull('owner_type')
            ->whereIn('directory', $directories)
            ->where('created_at', '<', $threshold->format(self::DATE_FORMAT))
            ->orderBy('id')
            ->limit($limit)
            ->lockForUpdate()
            ->get(['id', 'file_id']);

        if ($rows->isEmpty()) {
            return [];
        }

        // Only the rows still unattached: a form sent while they were being read keeps its own.
        EditorUpload::query()->whereIn('id', $rows->pluck('id')->all())->whereNull('owner_type')->delete();

        return $this->ids($rows->pluck('file_id')->all());
    }

    public function getUntrackedFileIds(array $directories, DateTimeImmutable $threshold, int $limit): array
    {
        if ($directories === []) {
            return [];
        }

        return $this->ids(
            Capsule::table('files')
                ->where(static function (Builder $query) use ($directories): void {
                    foreach ($directories as $directory) {
                        $query->orWhere('path', 'like', rtrim($directory, '/') . '/%');
                    }
                })
                ->where('created_at', '<', $threshold->format(self::DATE_FORMAT))
                ->whereNotExists(static function (Builder $query): void {
                    $query->selectRaw('1')->from('editor_uploads')->whereColumn('editor_uploads.file_id', 'files.id');
                })
                ->orderBy('id')
                ->limit($limit)
                ->pluck('id')
                ->all()
        );
    }

    /**
     * @param array<mixed> $values
     * @return list<int>
     */
    private function ids(array $values): array
    {
        return array_values(array_map('intval', array_filter($values, static fn(mixed $value): bool => $value !== null)));
    }
}
