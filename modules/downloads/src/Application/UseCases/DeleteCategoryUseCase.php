<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Domain\Services\ScreenService;
use Johncms\Modules\Downloads\Domain\Models\DownloadBookmark;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadComment;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Models\DownloadMoreFile;

final readonly class DeleteCategoryUseCase
{
    public function execute(DownloadCategory $category): void
    {
        DownloadFile::query()->where('refid', $category->id)->each(function (DownloadFile $file): void {
            foreach (ScreenService::getScreens($file->id) as $screen) {
                @unlink($screen['path']);
            }
            @rmdir(\UPLOAD_PATH . 'downloads' . \DS . 'screen' . \DS . $file->id);

            DownloadMoreFile::query()->where('refid', $file->id)->each(function (DownloadMoreFile $more) use ($file): void {
                @unlink($file->dir . '/' . $more->name);
            });
            DownloadMoreFile::query()->where('refid', $file->id)->delete();

            DownloadComment::query()->where('sub_id', $file->id)->delete();
            DownloadBookmark::query()->where('file_id', $file->id)->delete();

            @unlink($file->dir . '/' . $file->name);
        });

        DownloadFile::query()->where('refid', $category->id)->delete();

        $dir = $category->dir;
        $category->delete();
        @rmdir($dir);
    }
}
