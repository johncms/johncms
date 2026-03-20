<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\System\View\Render;

final readonly class DownloadFileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ForumFileRepositoryInterface $fileRepository,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render($this->render, $exception);
        }

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

    private function renderNotFound(): string
    {
        http_response_code(404);

        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Download file'),
                'type'          => 'alert-danger',
                'message'       => __('File does not exist'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Forum'),
            ]
        );
    }
}
