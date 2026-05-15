<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Users\User;

final readonly class MoveSectionController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(int $parentId, string $direction, int $positionIndex): never
    {
        if (! ($this->currentUser->rights > 4)) {
            http_response_code(403);
            exit;
        }

        if (! in_array($direction, ['up', 'down'], true) || $positionIndex < 1) {
            http_response_code(400);
            exit;
        }

        $sections = LibraryCategory::query()
            ->select(['id', 'pos'])
            ->where('parent', $parentId)
            ->orderBy('pos')
            ->get();

        $indexed = [];
        $i       = 1;
        foreach ($sections as $section) {
            $indexed[$i++] = ['id' => $section->id, 'pos' => $section->pos];
        }

        $swapIndex = $direction === 'up' ? $positionIndex - 1 : $positionIndex + 1;

        if (! isset($indexed[$positionIndex], $indexed[$swapIndex])) {
            http_response_code(400);
            exit;
        }

        LibraryCategory::query()->where('id', $indexed[$positionIndex]['id'])->update(['pos' => $indexed[$swapIndex]['pos']]);
        LibraryCategory::query()->where('id', $indexed[$swapIndex]['id'])->update(['pos' => $indexed[$positionIndex]['pos']]);

        $referer = $_SERVER['HTTP_REFERER'] ?? '/library/';
        header('Location: ' . $referer, true, 302);
        exit;
    }
}
