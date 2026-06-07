<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Admin\Application\Services\AdminUserRowMapper;
use Johncms\Modules\Admin\Application\UseCases\GetUserListUseCase;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class UserListController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private GetUserListUseCase $getUserListUseCase,
        private AdminUserRowMapper $rowMapper,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function __invoke(?string $sort = null): string
    {
        $sortMode = match ($sort) {
            'by-nick' => UserListSort::NICK,
            'by-ip'   => UserListSort::IP,
            default   => UserListSort::ID,
        };

        $baseUrl = match ($sortMode) {
            UserListSort::NICK => '/admin/users/by-nick',
            UserListSort::IP   => '/admin/users/by-ip',
            UserListSort::ID   => '/admin/users',
        };

        $page = max(1, (int) $this->request->getQuery('page', 1));
        $perPage = $this->currentUser->config->kmess;

        $result = $this->getUserListUseCase->execute($sortMode, $page, $perPage);
        $total = $result->users->total();

        $title = __('List of Users');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $page);
        $this->render->addData(
            [
                'title'      => $meta->title,
                'page_title' => $title,
                'usr_menu'   => ['userlist' => true],
            ]
        );

        return $this->render->render(
            'admin::userlist',
            [
                'users'      => $this->rowMapper->mapMany($result->users->getCollection()),
                'total'      => $total,
                'per_page'   => $perPage,
                'sort'       => $sortMode->value,
                'sort_links' => [
                    ['name' => 'ID', 'url' => '/admin/users', 'active' => $sortMode === UserListSort::ID],
                    ['name' => __('Nickname'), 'url' => '/admin/users/by-nick', 'active' => $sortMode === UserListSort::NICK],
                    ['name' => 'IP', 'url' => '/admin/users/by-ip', 'active' => $sortMode === UserListSort::IP],
                ],
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
