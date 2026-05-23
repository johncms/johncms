<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class FilesModerationController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private FilePresenter $filePresenter,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(): string
    {
        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add(__('Files awaiting moderation'));

        $this->render->addData([
            'title'      => __('Files awaiting moderation'),
            'page_title' => __('Files awaiting moderation'),
        ]);

        $acceptId = (int) $this->request->getQuery('accept', 0);
        if ($acceptId) {
            return $this->handleAcceptOne($acceptId);
        }

        if ($this->request->getMethod() === 'POST') {
            $post = $this->request->getParsedBody();
            if (isset($post['all_mod'])) {
                return $this->handleAcceptAll();
            }
        }

        return $this->showList();
    }

    private function handleAcceptOne(int $id): string
    {
        DownloadFile::query()->where('id', $id)->where('type', 3)->update(['type' => 2]);

        return $this->render->render('system::pages/result', [
            'title'         => __('Files awaiting moderation'),
            'type'          => 'alert-success',
            'message'       => __('File accepted'),
            'back_url'      => '/downloads/moderation',
            'back_url_name' => __('Files awaiting moderation'),
        ]);
    }

    private function handleAcceptAll(): string
    {
        DownloadFile::query()->where('type', 3)->update(['type' => 2]);

        return $this->render->render('system::pages/result', [
            'title'         => __('Files awaiting moderation'),
            'type'          => 'alert-success',
            'message'       => __('All files accepted'),
            'back_url'      => '/downloads/',
            'back_url_name' => __('Downloads'),
        ]);
    }

    private function showList(): string
    {
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $perPage = $this->currentUser->config->kmess;
        $start = ($page - 1) * $perPage;

        $total = DownloadFile::query()->where('type', 3)->count();
        $files = [];

        if ($total > 0) {
            $rows = DownloadFile::query()
                ->where('type', 3)
                ->orderByDesc('time')
                ->skip($start)
                ->take($perPage)
                ->get();

            foreach ($rows as $file) {
                $row = $this->filePresenter->present($file);
                $row['accept_url'] = '/downloads/moderation?accept=' . $file->id;
                $row['delete_url'] = '/downloads/delete-file/' . $file->id . '/';
                $files[] = $row;
            }
        }

        return $this->render->render('downloads::files_moderation', [
            'files'      => $files,
            'total'      => $total,
            'pagination' => $this->tools->displayPagination(
                '/downloads/moderation?',
                $start,
                $total,
                $perPage
            ),
            'urls'       => ['downloads' => '/downloads/'],
        ]);
    }
}
