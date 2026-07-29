<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application;

use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
use Johncms\Http\Request;

final readonly class LegacyRedirectHandler
{
    public function __construct(
        private LibraryCategoryPathService $categoryPathService,
        private LibraryArticlePathService $articlePathService,
    ) {
    }

    public function handle(Request $request): void
    {
        $id  = $request->queryInt('id');
        $act = $request->queryParam('act', '');
        $do  = $request->queryParam('do', '');

        if ($act === 'lastcom') {
            redirect('/library/latest-comments', 301);
        }

        if ($act === 'tags' && $request->query->has('tag')) {
            redirect('/library/tags?tag=' . urlencode($request->queryParam('tag')), 301);
        }

        if ($id > 0 && $do === 'dir') {
            $url = $this->categoryPathService->getCategoryUrlById($id) ?? '/library/';
            redirect($url, 301);
        }

        if ($id > 0 && $do === '') {
            $url = $this->articlePathService->getArticleUrlById($id) ?? '/library/';
            redirect($url, 301);
        }

        if ($act === 'download' && $id > 0) {
            $type = $request->queryParam('type', 'txt');
            $type = in_array($type, ['txt', 'fb2'], true) ? $type : 'txt';
            redirect('/library/article/' . $id . '/download/' . $type, 301);
        }
    }
}
