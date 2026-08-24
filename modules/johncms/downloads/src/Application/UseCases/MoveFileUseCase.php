<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Models\DownloadMoreFile;

final readonly class MoveFileUseCase
{
    public function execute(DownloadFile $file, DownloadCategory $target): void
    {
        DownloadMoreFile::query()->where('refid', $file->id)->each(function (DownloadMoreFile $more) use ($file, $target): void {
            copy($file->dir . '/' . $more->name, $target->dir . '/' . $more->name);
            @unlink($file->dir . '/' . $more->name);
        });

        $newName = $file->name;
        $newPath = $target->dir . '/' . $newName;
        if (is_file($newPath)) {
            $newName = time() . '_' . $file->name;
            $newPath = $target->dir . '/' . $newName;
        }

        copy($file->dir . '/' . $file->name, $newPath);
        @unlink($file->dir . '/' . $file->name);

        $file->update([
            'name'  => $newName,
            'dir'   => $target->dir,
            'refid' => $target->id,
        ]);
    }
}
