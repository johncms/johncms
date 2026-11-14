<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Downloads\Application\Access\DownloadsAccess;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Modules\Downloads\Application\Services\CatalogPresenter;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;

final readonly class DownloadCategoryController
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private NavChain $navChain,
        private DownloadCategoryPathService $categoryPathService,
        private CatalogPresenter $catalog,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private DownloadsAccess $downloadsAccess,
    ) {
    }

    public function __invoke(Request $request, string $categoryPath): ViewResponse
    {
        $category = $this->categoryPathService->findCategoryByPath($categoryPath);
        if ($category === null) {
            pageNotFound();
        }

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->buildNavChain($category);

        $title = $category->rus_name;
        $categoryUrl = $this->categoryPathService->getCategoryUrl($category);

        $categories = $this->catalog->categories($category->id);
        $totalFiles = $this->catalog->countFiles($category->id);
        $pagination = $this->paginationFactory->create(total: $totalFiles, fragment: 'downloads-files');
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $sort = $this->catalog->sort($request->query->getString('sort'), $request->query->getString('order'));
        $meta = new PageMeta($title, $pagination->getCurrentPage());
        $config = config('johncms');
        $totalNew = DownloadFile::query()
            ->where('type', 2)
            ->where('time', '>', time() - FilePresenter::NEW_FILE_PERIOD)
            ->where('dir', 'like', $category->dir . '%')
            ->count();

        return new ViewResponse('@downloads/public/index.twig', [
            'title'       => $meta->title,
            'page_title'  => $title,
            'description' => $meta->description,
            'id'          => $category->id,
            'summary'     => (string) $category->desc,
            'stat'        => [
                'sections' => count($categories),
                'files'    => (int) $category->total,
            ],
            'total_new'   => $totalNew,
            'new_url'     => '/downloads/new/?id=' . $category->id,
            'categories'  => $categories,
            'files'       => $totalFiles > 0 ? $this->catalog->files($category->id, $sort, $pagination->getOffset(), $pagination->getPerPage()) : [],
            'total_files' => $totalFiles,
            'sort'        => $totalFiles > 1 ? $this->catalog->sortLinks($categoryUrl, $sort) : null,
            'pagination'  => $pagination->hasPages() ? $pagination->render(withRange: true) : null,
            'can_upload'  => $this->downloadsAccess->mayUpload($category),
            'can_manage'  => $this->accessChecker->allows(DownloadsPermissions::MODERATE),
            'can_review'  => ! empty($config['mod_down_comm']) || $this->accessChecker->allows(DownloadsPermissions::COMMENTS_ALWAYS_VIEW),
        ]);
    }

    private function buildNavChain(DownloadCategory $category): void
    {
        $ancestors = [];
        $current = $category;
        while ($current->refid > 0) {
            $parent = DownloadCategory::query()->find($current->refid);
            if ($parent === null) {
                break;
            }
            $ancestors[] = $parent;
            $current = $parent;
        }
        foreach (array_reverse($ancestors) as $ancestor) {
            $this->navChain->add($ancestor->rus_name, $this->categoryPathService->getCategoryUrl($ancestor));
        }
        $this->navChain->add($category->rus_name);
    }
}
