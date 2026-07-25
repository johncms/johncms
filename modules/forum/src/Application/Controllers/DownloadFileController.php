<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\Response;

final readonly class DownloadFileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private ForumFileRepositoryInterface $fileRepository,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): Response
    {
        $file = $this->fileRepository->findById($id);
        if ($file === null) {
            return $this->renderNotFound();
        }

        $filePath = UPLOAD_PATH . 'forum/attach/' . $file->filename;
        if (! is_file($filePath)) {
            return $this->renderNotFound();
        }

        $file->dlcount = (int) $file->dlcount + 1;
        $this->fileRepository->save($file);

        redirect('/upload/forum/attach/' . $file->filename);
    }

    private function renderNotFound(): Response
    {
        return new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Download file'),
                    'type'          => 'alert-danger',
                    'message'       => __('File does not exist'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Forum'),
                ]
            ),
            Response::HTTP_NOT_FOUND
        );
    }
}
