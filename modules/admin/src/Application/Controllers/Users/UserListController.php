<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Admin\Application\Services\AdminUserRowMapper;
use Johncms\Modules\Admin\Application\UseCases\GetUserListUseCase;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class UserListController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetUserListUseCase $getUserListUseCase,
        private AdminUserRowMapper $rowMapper,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
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

        $pagination = $this->paginationFactory->create($this->getUserListUseCase->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->getUserListUseCase->getPage($sortMode, $pagination->getPerPage(), $pagination->getOffset());

        $title = __('List of Users');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $pagination->getCurrentPage());
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
                'users'      => $this->rowMapper->mapMany($result->users),
                'total'      => $pagination->getTotal(),
                'per_page'   => $pagination->getPerPage(),
                'sort'       => $sortMode->value,
                'sort_links' => [
                    ['name' => 'ID', 'url' => '/admin/users', 'active' => $sortMode === UserListSort::ID],
                    ['name' => __('Nickname'), 'url' => '/admin/users/by-nick', 'active' => $sortMode === UserListSort::NICK],
                    ['name' => 'IP', 'url' => '/admin/users/by-ip', 'active' => $sortMode === UserListSort::IP],
                ],
                'pagination' => $pagination->render(),
            ]
        );
    }
}
