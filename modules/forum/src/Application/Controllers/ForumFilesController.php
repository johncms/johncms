<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\DTO\ForumFilesQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumFilesContextNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewForumFilesUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class ForumFilesController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private ViewForumFilesUseCase $viewForumFilesUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->render->render(
                'system::pages/result',
                $this->forumAccessResponseBuilder->forException($exception)
            );
        }

        $query = new ForumFilesQueryDTO(
            start: max(0, (int) $this->request->getQuery('start', 0)),
            contextCategoryId: max(0, (int) $this->request->getQuery('c', 0)),
            contextSectionId: max(0, (int) $this->request->getQuery('s', 0)),
            contextTopicId: max(0, (int) $this->request->getQuery('t', 0)),
            fileType: $this->normalizeFileType((int) $this->request->getQuery('do', 0)),
            isNew: $this->request->getQuery('new') !== null,
        );

        try {
            $result = $this->viewForumFilesUseCase->execute($query);
        } catch (ForumFilesContextNotFoundException) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Forum Files'),
                    'page_title'    => __('Forum Files'),
                    'type'          => 'alert-danger',
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
}
