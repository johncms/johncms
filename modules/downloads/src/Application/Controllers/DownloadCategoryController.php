<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Users\User;

final readonly class DownloadCategoryController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private FilePresenter $filePresenter,
        private DownloadCategoryPathService $categoryPathService,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
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

        $canUpload = (bool) $category->field && $this->currentUser->isValid();

        $old = time() - FilePresenter::NEW_FILE_PERIOD;

        $totalNew = DownloadFile::query()
            ->where('type', 2)
            ->where('time', '>', $old)
            ->where('dir', 'like', $category->dir . '%')
            ->count();

        $categoryUrl = $this->categoryPathService->getCategoryUrl($category);

        $urls = [
            'downloads'   => '/downloads/',
            'new'         => $totalNew > 0 ? '/downloads/new/?dir=' . urlencode($category->dir) : '',
            'sort_action' => $categoryUrl,
        ];

        $totalCat = DownloadCategory::query()->where('refid', $category->id)->count();
        $categories = [];
        if ($totalCat > 0) {
            $hasEdit = $this->currentUser->rights === 4 || $this->currentUser->rights >= 6;
            DownloadCategory::query()->where('refid', $category->id)->orderBy('sort')->each(
                function (DownloadCategory $cat) use (&$categories, $hasEdit): void {
                    $categories[] = [
                        'id'         => $cat->id,
                        'rus_name'   => $cat->rus_name,
                        'desc'       => $cat->desc,
                        'field'      => $cat->field,
                        'text'       => $cat->text,
                        'total'      => $cat->total,
                        'url'        => $this->categoryPathService->getCategoryUrl($cat),
                        'up_url'     => '/downloads/categories/' . $cat->id . '/edit?do=up',
                        'down_url'   => '/downloads/categories/' . $cat->id . '/edit?do=down',
                        'edit_url'   => '/downloads/categories/' . $cat->id . '/edit',
                        'delete_url' => '/downloads/categories/' . $cat->id . '/delete',
                        'has_edit'   => $hasEdit,
                    ];
                }
            );
        }

        $totalFiles = DownloadFile::query()->where('refid', $category->id)->where('type', '<', 3)->count();

        $pagination = $this->paginationFactory->create($totalFiles);
        if ($request->getMethod() !== 'POST') {
            $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }
        }

        $meta = new PageMeta($title, $pagination->getCurrentPage());

        $files = [];

        if ($totalFiles > 1 && $request->getMethod() === 'POST') {
            $post = $request->request->all();
            if (isset($post['sort_down'])) {
                $this->session->set('sort_down', $post['sort_down'] ? 1 : 0);
            }
            if (isset($post['sort_down2'])) {
                $this->session->set('sort_down2', $post['sort_down2'] ? 1 : 0);
            }
        }

        $sortByName = (bool) $this->session->get('sort_down', 0);
        $sortAscending = (bool) $this->session->get('sort_down2', 0);

        if ($totalFiles > 0) {
            $sortColumn = $sortByName ? 'name' : 'time';
            $sortDir = $sortAscending ? 'asc' : 'desc';

            $rows = DownloadFile::query()
                ->where('refid', $category->id)
                ->where('type', '<', 3)
                ->orderBy('type')
                ->orderBy($sortColumn, $sortDir)
                ->offset($pagination->getOffset())
                ->limit($pagination->getPerPage())
                ->get();

            foreach ($rows as $file) {
                $files[] = $this->filePresenter->present($file);
            }
        }

        $config = config('johncms');

        return new ViewResponse('@downloads/public/index.twig', [
            'title'          => $meta->title,
            'page_title'     => $title,
            'description'    => $meta->description,
            'id'             => $category->id,
            'urls'           => $urls,
            'pagination'     => $pagination->hasPages() ? $pagination->render() : null,
            'files'          => $files,
            'total_files'    => $totalFiles,
            'total_new'      => $totalNew,
            'categories'     => $categories,
            'total_cat'      => $totalCat,
            'can_upload'     => $canUpload,
            'can_manage'     => $this->currentUser->rights === 4 || $this->currentUser->rights >= 6,
            'can_review'     => $this->currentUser->rights >= 7 || ! empty($config['mod_down_comm']),
            'sort_by_name'   => $sortByName,
            'sort_ascending' => $sortAscending,
            'downloads_open' => (bool) $config['mod_down'],
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
