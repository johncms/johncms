<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;

final readonly class FilesModerationController
{
    public function __construct(
        private NavChain $navChain,
        private FilePresenter $filePresenter,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add(__('Files awaiting moderation'));

        if ($request->isPost()) {
            return $this->handleAcceptOne($request->bodyInt('accept'));
        }

        return $this->showList();
    }

    /** Carried out only from the confirmed form of the list (RequireConfirmationMiddleware). */
    public function acceptAll(): ViewResponse
    {
        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add(__('Files awaiting moderation'));

        DownloadFile::query()->where('type', 3)->update(['type' => 2]);

        return new ViewResponse('@theme/pages/result.twig', [
            'title'         => __('Files awaiting moderation'),
            'type'          => 'alert-success',
            'message'       => __('All files accepted'),
            'back_url'      => '/downloads/',
            'back_url_name' => __('Downloads'),
        ]);
    }

    private function handleAcceptOne(int $id): ViewResponse
    {
        DownloadFile::query()->where('id', $id)->where('type', 3)->update(['type' => 2]);

        return new ViewResponse('@theme/pages/result.twig', [
            'title'         => __('Files awaiting moderation'),
            'type'          => 'alert-success',
            'message'       => __('File accepted'),
            'back_url'      => '/downloads/moderation',
            'back_url_name' => __('Files awaiting moderation'),
        ]);
    }

    private function showList(): ViewResponse
    {
        $total = DownloadFile::query()->where('type', 3)->count();

        $pagination = $this->paginationFactory->create($total);
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $files = [];

        if ($total > 0) {
            $rows = DownloadFile::query()
                ->where('type', 3)
                ->orderByDesc('time')
                ->skip($pagination->getOffset())
                ->take($pagination->getPerPage())
                ->get();

            foreach ($rows as $file) {
                $row = $this->filePresenter->present($file);
                $row['accept_url'] = '/downloads/moderation';
                $row['delete_url'] = '/downloads/delete-file/' . $file->id . '/';
                $files[] = $row;
            }
        }

        return new ViewResponse('@downloads/public/moderation.twig', [
            'title'      => __('Files awaiting moderation'),
            'page_title' => __('Files awaiting moderation'),
            'files'      => $files,
            'total'      => $total,
            'pagination' => $pagination->hasPages() ? $pagination->render() : null,
            'urls'       => ['downloads' => '/downloads/'],
        ]);
    }
}
