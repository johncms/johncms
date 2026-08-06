<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Models\DownloadMoreFile;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class LoadFileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Session $session,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(Request $request, int $id): RedirectResponse|ViewResponse
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

        $moreId = abs($request->queryInt('more'));
        if ($moreId > 0) {
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

        return new RedirectResponse($link);
    }

    private function notFound(): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => __('File not found'),
                'type'          => 'alert-danger',
                'message'       => __('File not found'),
                'back_url'      => '/downloads/',
                'back_url_name' => __('Downloads'),
            ],
            Response::HTTP_NOT_FOUND
        );
    }
}
