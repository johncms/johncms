<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\DTO\ForumFilesQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\ViewForumFilesUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;

final readonly class ForumFilesController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewForumFilesUseCase $viewForumFilesUseCase,
        private PaginationFactory $paginationFactory,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $page = max(1, $request->queryInt('page', 1));
        $start = ($page - 1) * (int) $this->currentUser->config->kmess;

        $query = new ForumFilesQueryDTO(
            start: $start,
            contextCategoryId: max(0, $request->queryInt('c', 0)),
            contextSectionId: max(0, $request->queryInt('s', 0)),
            contextTopicId: max(0, $request->queryInt('t', 0)),
            fileType: $this->normalizeFileType($request->queryInt('do', 0)),
            isNew: $request->query->has('new'),
        );

        try {
            $result = $this->viewForumFilesUseCase->execute($query);
        } catch (ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->viewResponse(
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
        if ($result->template === '@forum/public/files-list.twig') {
            $pagination = $this->paginationFactory->create((int) ($viewData['total'] ?? 0), null, 'page', $page);
            $viewData['pagination'] = $pagination->hasPages() ? $pagination->render() : null;
        }

        return new ViewResponse($result->template, $viewData);
    }

    private function normalizeFileType(int $fileType): int
    {
        return $fileType > 0 && $fileType < 10 ? $fileType : 0;
    }
}
