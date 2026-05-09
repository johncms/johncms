<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Domain\Models\DownloadBookmark;

final readonly class ToggleBookmarkUseCase
{
    public function execute(int $fileId, int $userId, ?string $action): int
    {
        $exists = DownloadBookmark::query()
            ->where('file_id', $fileId)
            ->where('user_id', $userId)
            ->exists();

        if ($action === 'add' && ! $exists) {
            DownloadBookmark::query()->create(['file_id' => $fileId, 'user_id' => $userId]);
            return 1;
        }

        if ($action === 'remove' && $exists) {
            DownloadBookmark::query()
                ->where('file_id', $fileId)
                ->where('user_id', $userId)
                ->delete();
            return 0;
        }

        return $exists ? 1 : 0;
    }
}
