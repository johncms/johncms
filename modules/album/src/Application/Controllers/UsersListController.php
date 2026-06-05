<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Album\Application\UseCases\GetUsersListUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class UsersListController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private GetUsersListUseCase $useCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(?string $filter = null): string
    {
        $sex = match ($filter) {
            'boys'  => 'm',
            'girls' => 'zh',
            default => null,
        };

        $page = max(1, (int) $this->request->getQuery('page', 1));
        $perPage = $this->currentUser->config->kmess;

        $result = $this->useCase->execute($sex, $page, $perPage);

        $users = [];
        foreach ($result->users->items() as $userModel) {
            $users[] = [
                'id'             => $userModel->id,
                'nick'           => $userModel->name,
                'user_is_online' => $userModel->is_online,
                // The user albums list is still served by the legacy controller until it is migrated.
                'album_url'      => '/album/list?user=' . $userModel->id,
                'count_albums'   => $userModel->count_albums ?? 0,
                'count'          => $userModel->count ?? 0,
            ];
        }

        $title = __('List of users');
        $this->navChain->add(__('Albums'), '/album');
        $this->navChain->add($title);

        $baseUrl = $filter === null ? '/album/users' : '/album/users/' . $filter;
        $total = $result->users->total();

        $meta = new PageMeta($title, $page);
        $this->render->addData([
            'title'      => $meta->title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'album::users',
            [
                'filters'    => [
                    'all'   => ['name' => __('All'), 'url' => '/album/users', 'active' => $filter === null],
                    'boys'  => ['name' => __('Guys'), 'url' => '/album/users/boys', 'active' => $filter === 'boys'],
                    'girls' => ['name' => __('Girls'), 'url' => '/album/users/girls', 'active' => $filter === 'girls'],
                ],
                'users'      => $users,
                'total'      => $total,
                'per_page'   => $perPage,
                'pagination' => $this->tools->displayPagination(
                    $baseUrl . '?',
                    ($page - 1) * $perPage,
                    $total,
                    $perPage
                ),
            ]
        );
    }
}
