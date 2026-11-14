<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Modules\Downloads\Application\Services\CatalogPresenter;
use Johncms\Modules\Downloads\Application\Services\DownloadLegacyRedirectResolver;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\NavChain;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class IndexController
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private NavChain $navChain,
        private DownloadLegacyRedirectResolver $legacyRedirectResolver,
        private CatalogPresenter $catalog,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse|ViewResponse
    {
        $redirect = $this->legacyRedirectResolver->resolve($request->query->all());
        if ($redirect !== null) {
            return new RedirectResponse($redirect, Response::HTTP_MOVED_PERMANENTLY);
        }

        $this->navChain->add(__('Downloads'));

        $categories = $this->catalog->categories(0);
        $totalFiles = $this->catalog->countFiles(0);
        $pagination = $this->paginationFactory->create(total: $totalFiles, fragment: 'downloads-files');
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $sort = $this->catalog->sort($request->query->getString('sort'), $request->query->getString('order'));
        $meta = new PageMeta(__('Downloads'), $pagination->getCurrentPage());
        $config = config('johncms');

        return new ViewResponse('@downloads/public/index.twig', [
            'title'       => $meta->title,
            'page_title'  => __('Downloads'),
            'description' => $meta->description,
            'id'          => 0,
            'summary'     => '',
            'stat'        => [
                'sections' => count($categories),
                'files'    => DownloadFile::query()->where('type', 2)->count(),
            ],
            'total_new'   => DownloadFile::query()->where('type', 2)->where('time', '>', time() - FilePresenter::NEW_FILE_PERIOD)->count(),
            'new_url'     => '/downloads/new/',
            'categories'  => $categories,
            'files'       => $totalFiles > 0 ? $this->catalog->files(0, $sort, $pagination->getOffset(), $pagination->getPerPage()) : [],
            'total_files' => $totalFiles,
            'sort'        => $totalFiles > 1 ? $this->catalog->sortLinks('/downloads/', $sort) : null,
            'pagination'  => $pagination->hasPages() ? $pagination->render(withRange: true) : null,
            'can_upload'  => false,
            'can_manage'  => $this->accessChecker->allows(DownloadsPermissions::MODERATE),
            'can_review'  => ! empty($config['mod_down_comm']) || $this->accessChecker->allows(DownloadsPermissions::COMMENTS_ALWAYS_VIEW),
        ]);
    }
}
