<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Models\DownloadMoreFile;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class LoadFileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Session $session,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(int $id): string
    {
        $file = DownloadFile::query()
            ->where('id', $id)
            ->whereIn('type', [2, 3])
            ->first();

        if ($file === null || ! is_file($file->dir . '/' . $file->name)) {
            return $this->notFound();
        }

        if ($file->type === 3 && $this->currentUser->rights < 6 && $this->currentUser->rights !== 4) {
            return $this->notFound();
        }

        $link = '/' . $file->dir . '/' . $file->name;

        $moreId = $this->request->getQuery('more');
        if ($moreId !== null) {
            $moreId = abs((int) $moreId);
            $moreFile = DownloadMoreFile::query()
                ->where('refid', $id)
                ->where('id', $moreId)
                ->first();

            if ($moreFile === null || ! is_file($file->dir . '/' . $moreFile->name)) {
                return $this->notFound();
            }

            $link = '/' . $file->dir . '/' . $moreFile->name;
        }

        $sessionKey = 'down_' . $id;
        if (! $this->session->has($sessionKey)) {
            DownloadFile::query()->where('id', $id)->increment('field');
            $this->session->set($sessionKey, 1);
        }

        http_response_code(302);
        header('Location: ' . $link);
        exit;
    }

    private function notFound(): string
    {
        http_response_code(404);
        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('File not found'),
                'type'          => 'alert-danger',
                'message'       => __('File not found'),
                'back_url'      => '/downloads/',
                'back_url_name' => __('Downloads'),
            ]
        );
    }
}
