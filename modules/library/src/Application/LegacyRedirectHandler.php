<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application;

use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
use Johncms\Http\Request;

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
        $id  = $this->request->queryInt('id');
        $act = $this->request->queryParam('act', '');
        $do  = $this->request->queryParam('do', '');

        if ($act === 'lastcom') {
            redirect('/library/latest-comments', 301);
        }

        if ($act === 'tags' && $this->request->query->has('tag')) {
            redirect('/library/tags?tag=' . urlencode($this->request->queryParam('tag')), 301);
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
            $type = $this->request->queryParam('type', 'txt');
            $type = in_array($type, ['txt', 'fb2'], true) ? $type : 'txt';
            redirect('/library/article/' . $id . '/download/' . $type, 301);
        }
    }
}
