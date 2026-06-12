<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\DTO\ForumFilesQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
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
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewForumFilesUseCase $viewForumFilesUseCase,
        private PaginationFactory $paginationFactory,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(): string
    {
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $start = ($page - 1) * (int) $this->currentUser->config->kmess;

        $query = new ForumFilesQueryDTO(
            start: $start,
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

        $viewData = $result->viewData;
        if ($result->template === 'forum::files_list') {
            $pagination = $this->paginationFactory->create((int) ($viewData['total'] ?? 0), null, 'page', $page);
            $viewData['pagination'] = $pagination->render();
        }

        return $this->render->render($result->template, $viewData);
    }

    private function normalizeFileType(int $fileType): int
    {
        return $fileType > 0 && $fileType < 10 ? $fileType : 0;
    }
}
