<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class RecountController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $id = max(0, $request->queryInt('id', 0));

        DownloadCategory::query()->each(function (DownloadCategory $category): void {
            $count = DownloadFile::query()
                ->where('type', 2)
                ->where('dir', 'like', $category->dir . '%')
                ->count();
            $category->update(['total' => $count]);
        });

        return new RedirectResponse('/downloads/' . ($id ? '?id=' . $id : ''));
    }
}
