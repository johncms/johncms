<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application;

use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
use Johncms\System\Http\Request;

final readonly class LegacyRedirectHandler
{
    public function __construct(
        private Request $request,
        private LibraryCategoryPathService $categoryPathService,
        private LibraryArticlePathService $articlePathService,
    ) {
    }

    public function handle(): void
    {
        $id  = $this->request->getQuery('id', 0, FILTER_VALIDATE_INT);
        $act = (string) $this->request->getQuery('act', '');
        $do  = (string) $this->request->getQuery('do', '');

        if ($act === 'lastcom') {
            header('Location: /library/latest-comments', true, 301);
            exit;
        }

        if ($act === 'tags' && $this->request->getQuery('tag') !== null) {
            header('Location: /library/tags?tag=' . urlencode((string) $this->request->getQuery('tag')), true, 301);
            exit;
        }

        if ($id > 0 && $do === 'dir') {
            $url = $this->categoryPathService->getCategoryUrlById($id) ?? '/library/';
            header('Location: ' . $url, true, 301);
            exit;
        }

        if ($id > 0 && $do === '') {
            $url = $this->articlePathService->getArticleUrlById($id) ?? '/library/';
            header('Location: ' . $url, true, 301);
            exit;
        }

        if ($act === 'download' && $id > 0) {
            $type = $this->request->getQuery('type', 'txt');
            $type = in_array($type, ['txt', 'fb2'], true) ? $type : 'txt';
            header('Location: /library/article/' . $id . '/download/' . $type, true, 301);
            exit;
        }
    }
}
