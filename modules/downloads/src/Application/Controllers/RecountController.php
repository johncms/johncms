<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class RecountController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Request $request,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(): RedirectResponse
    {
        $id = max(0, $this->request->queryInt('id', 0));

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
