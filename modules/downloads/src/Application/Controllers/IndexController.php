<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Application\Services\DownloadLegacyRedirectResolver;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class IndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private FilePresenter $filePresenter,
        private DownloadLegacyRedirectResolver $legacyRedirectResolver,
        private DownloadCategoryPathService $categoryPathService,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(): Response
    {
        $redirect = $this->legacyRedirectResolver->resolve($this->request->query->all());
        if ($redirect !== null) {
            return new RedirectResponse($redirect, Response::HTTP_MOVED_PERMANENTLY);
        }

        $this->navChain->add(__('Downloads'), '/downloads/');

        $title = __('Downloads');
        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        $old = time() - FilePresenter::NEW_FILE_PERIOD;

        $totalNew = DownloadFile::query()
            ->where('type', 2)
            ->where('time', '>', $old)
            ->count();

        $urls = [
            'downloads'   => '/downloads/',
            'new'         => $totalNew > 0 ? '/downloads/new/' : '',
            'sort_action' => '',
        ];

        $totalCat = DownloadCategory::query()->where('refid', 0)->count();
        $categories = [];
        if ($totalCat > 0) {
            $hasEdit = $this->currentUser->rights === 4 || $this->currentUser->rights >= 6;
            DownloadCategory::query()->where('refid', 0)->orderBy('sort')->each(
                function (DownloadCategory $cat) use (&$categories, $hasEdit): void {
                    $categories[] = [
                        'id'         => $cat->id,
                        'rus_name'   => htmlspecialchars($cat->rus_name),
                        'desc'       => htmlspecialchars($cat->desc),
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

        $totalFiles = DownloadFile::query()->where('refid', 0)->where('type', '<', 3)->count();

        $pagination = $this->paginationFactory->create($totalFiles);
        if ($this->request->getMethod() !== 'POST') {
            $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }
        }

        $files = [];

        if ($totalFiles > 0) {
            if ($totalFiles > 1) {
                if ($this->request->getMethod() === 'POST') {
                    $post = $this->request->request->all();
                    if (isset($post['sort_down'])) {
                        $this->session->set('sort_down', $post['sort_down'] ? 1 : 0);
                    }
                    if (isset($post['sort_down2'])) {
                        $this->session->set('sort_down2', $post['sort_down2'] ? 1 : 0);
                    }
                }
            }

            $sortColumn = ($this->session->get('sort_down', 0)) ? 'name' : 'time';
            $sortDir = ($this->session->get('sort_down2', 0)) ? 'asc' : 'desc';

            $rows = DownloadFile::query()
                ->where('refid', 0)
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

        return new Response($this->render->render('downloads::index', [
            'id'          => 0,
            'urls'        => $urls,
            'pagination'  => $pagination->render(),
            'files'       => $files,
            'total_files' => $totalFiles,
            'total_new'   => $totalNew,
            'categories'  => $categories,
            'total_cat'   => $totalCat,
            'can_upload'  => false,
        ]));
    }
}
