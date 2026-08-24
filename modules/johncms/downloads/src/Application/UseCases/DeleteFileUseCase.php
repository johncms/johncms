<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Domain\Services\ScreenService;
use Johncms\Modules\Downloads\Application\Exceptions\FileNotFoundException;
use Johncms\Modules\Downloads\Domain\Models\DownloadBookmark;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadComment;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Models\DownloadMoreFile;

final readonly class DeleteFileUseCase
{
    /**
     * @throws FileNotFoundException
     */
    public function execute(int $id): void
    {
        $file = DownloadFile::query()
            ->where('id', $id)
            ->whereIn('type', [2, 3])
            ->first();

        if ($file === null || ! is_file($file->dir . '/' . $file->name)) {
            throw new FileNotFoundException();
        }

        foreach (ScreenService::getScreens($id) as $screen) {
            @unlink($screen['path']);
        }
        @rmdir(\UPLOAD_PATH . 'downloads' . \DS . 'screen' . \DS . $id);

        DownloadMoreFile::query()->where('refid', $id)->each(function (DownloadMoreFile $more) use ($file): void {
            if (is_file($file->dir . '/' . $more->name)) {
                @unlink($file->dir . '/' . $more->name);
            }
        });
        DownloadMoreFile::query()->where('refid', $id)->delete();

        DownloadBookmark::query()->where('file_id', $id)->delete();
        DownloadComment::query()->where('sub_id', $id)->delete();

        @unlink($file->dir . '/' . $file->name);

        $this->decrementCategoryCounters((int) $file->refid);

        DownloadFile::query()->where('id', $id)->delete();
    }

    private function decrementCategoryCounters(int $categoryId): void
    {
        $ids = [];
        $dirid = $categoryId;
        while ($dirid !== 0) {
            $ids[] = $dirid;
            $cat = DownloadCategory::query()->select('refid')->find($dirid);
            if ($cat === null) {
                break;
            }
            $dirid = (int) $cat->refid;
        }

        if ($ids) {
            DownloadCategory::query()->whereIn('id', $ids)->decrement('total');
        }
    }
}
