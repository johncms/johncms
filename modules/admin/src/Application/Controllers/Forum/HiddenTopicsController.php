<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Forum;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Admin\Application\Services\HiddenTopicRowMapper;
use Johncms\Modules\Admin\Application\UseCases\ManageHiddenForumUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class HiddenTopicsController
{
    private const URL = '/admin/forum/hidden-topics';

    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private ManageHiddenForumUseCase $manageHidden,
        private HiddenTopicRowMapper $rowMapper,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function index(Request $request): ViewResponse
    {
        [$userId, $sectionId, $filterLink, $filteredBy] = $this->filters($request);

        $pagination = $this->paginationFactory->create($this->manageHidden->countTopics($userId, $sectionId));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $topics = $this->manageHidden->topicsPage($userId, $sectionId, $pagination->getPerPage(), $pagination->getOffset());
        $total = $pagination->getTotal();

        $title = __('Hidden topics');
        $this->navChain->add(__('Forum Management'), '/admin/forum');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $pagination->getCurrentPage());

        return new ViewResponse('@admin/forum-hidden-topics.twig', [
            'title'        => $meta->title,
            'page_title'   => $title,
            'module_menu'  => ['forum' => true],
            'items'        => $this->rowMapper->mapMany($topics),
            'total'        => $total,
            'per_page'     => $pagination->getPerPage(),
            'filtered_by'  => (string) $filteredBy,
            'reset_filter' => self::URL,
            'del_all_url'  => $this->currentUser->rights === 9 && $total > 0 ? self::URL . '/delete' . $filterLink : '',
            'pagination'   => $pagination->render(),
        ]);
    }

    public function deleteAll(Request $request): ViewResponse
    {
        if (! $this->isCsrfValid($request) || $this->currentUser->rights !== 9) {
            redirect(self::URL);
        }

        [$userId, $sectionId] = $this->filters($request);
        $this->manageHidden->purgeTopics($userId, $sectionId);

        redirect(self::URL);
    }

    /**
     * @return array{0: int|null, 1: int|null, 2: string, 3: string|null}
     */
    private function filters(Request $request): array
    {
        $userId = filter_var($request->queryParam('usort'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if ($userId !== null) {
            return [abs((int) $userId), null, '?usort=' . abs((int) $userId), __('by author')];
        }

        $sectionId = filter_var($request->queryParam('rsort'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if ($sectionId !== null) {
            return [null, abs((int) $sectionId), '?rsort=' . abs((int) $sectionId), __('by section')];
        }

        return [null, null, '', null];
    }

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }
}
