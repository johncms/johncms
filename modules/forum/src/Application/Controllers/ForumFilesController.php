<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\DTO\ForumFilesQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewForumFilesUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class ForumFilesController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewForumFilesUseCase $viewForumFilesUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render($this->render, $exception);
        }

        $query = new ForumFilesQueryDTO(
            start: $this->resolveStart(),
            contextCategoryId: max(0, (int) $this->request->getQuery('c', 0)),
            contextSectionId: max(0, (int) $this->request->getQuery('s', 0)),
            contextTopicId: max(0, (int) $this->request->getQuery('t', 0)),
            fileType: $this->normalizeFileType((int) $this->request->getQuery('do', 0)),
            isNew: $this->request->getQuery('new') !== null,
        );

        try {
            $result = $this->viewForumFilesUseCase->execute($query);
        } catch (ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'title'         => __('Forum Files'),
                    'page_title'    => __('Forum Files'),
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $this->navChain->add(__('Forum'), '/forum/');
        if ($result->contextName !== null && $result->contextUrl !== null) {
            $this->navChain->add($result->contextName, $result->contextUrl);
        }
        $this->navChain->add($result->caption);

        return $this->render->render($result->template, $result->viewData);
    }

    private function normalizeFileType(int $fileType): int
    {
        return $fileType > 0 && $fileType < 10 ? $fileType : 0;
    }

    private function resolveStart(): int
    {
        $page = max(1, (int) $this->request->getQuery('page', 1));

        return ($page - 1) * (int) $this->currentUser->config->kmess;
    }
}
