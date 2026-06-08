<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Forum;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Admin\Application\Services\HiddenTopicRowMapper;
use Johncms\Modules\Admin\Application\UseCases\ManageHiddenForumUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class HiddenTopicsController
{
    private const URL = '/admin/forum/hidden-topics';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private ManageHiddenForumUseCase $manageHidden,
        private HiddenTopicRowMapper $rowMapper,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(): string
    {
        [$userId, $sectionId, $filterLink, $filteredBy] = $this->filters();
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $perPage = $this->currentUser->config->kmess;

        $topics = $this->manageHidden->topics($userId, $sectionId, $page, $perPage);
        $total = $topics->total();

        $title = __('Hidden topics');
        $this->navChain->add(__('Forum Management'), '/admin/forum');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $page);
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $title,
            'module_menu' => ['forum' => true],
        ]);

        return $this->render->render('admin::forum/hidden_topics', [
            'items'        => $this->rowMapper->mapMany($topics->getCollection()),
            'total'        => $total,
            'per_page'     => $perPage,
            'filtered_by'  => $filteredBy,
            'reset_filter' => self::URL,
            'del_all_url'  => $this->currentUser->rights === 9 && $total > 0 ? self::URL . '/delete' . $filterLink : null,
            'pagination'   => $this->tools->displayPagination(self::URL . '?', ($page - 1) * $perPage, $total, $perPage),
        ]);
    }

    public function deleteAll(): string
    {
        if (! $this->isCsrfValid() || $this->currentUser->rights !== 9) {
            redirect(self::URL);
        }

        [$userId, $sectionId] = $this->filters();
        $this->manageHidden->purgeTopics($userId, $sectionId);

        redirect(self::URL);
    }

    /**
     * @return array{0: int|null, 1: int|null, 2: string, 3: string|null}
     */
    private function filters(): array
    {
        $userId = $this->request->getQuery('usort', null, FILTER_VALIDATE_INT);
        if ($userId !== null) {
            return [abs((int) $userId), null, '?usort=' . abs((int) $userId), __('by author')];
        }

        $sectionId = $this->request->getQuery('rsort', null, FILTER_VALIDATE_INT);
        if ($sectionId !== null) {
            return [null, abs((int) $sectionId), '?rsort=' . abs((int) $sectionId), __('by section')];
        }

        return [null, null, '', null];
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }
}
