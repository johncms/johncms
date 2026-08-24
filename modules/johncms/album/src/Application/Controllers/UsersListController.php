<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\PageMeta;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Album\Application\UseCases\GetUsersListUseCase;
use Johncms\NavChain;

final readonly class UsersListController
{
    public function __construct(
        private NavChain $navChain,
        private GetUsersListUseCase $useCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(?string $filter = null): ViewResponse
    {
        $sex = match ($filter) {
            'boys'  => 'm',
            'girls' => 'zh',
            default => null,
        };

        $title = __('List of users');
        $this->navChain->add(__('Albums'), '/album');
        $this->navChain->add($title);

        $pagination = $this->paginationFactory->create($this->useCase->count($sex));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $total = $pagination->getTotal();
        $userModels = $total > 0
            ? $this->useCase->getPage($sex, $pagination->getPerPage(), $pagination->getOffset())
            : [];

        $users = [];
        foreach ($userModels as $userModel) {
            $users[] = [
                'id'             => $userModel->id,
                'nick'           => $userModel->name,
                'user_is_online' => $userModel->is_online,
                'album_url'      => '/album/user/' . $userModel->id,
                'count_albums'   => $userModel->count_albums ?? 0,
                'count'          => $userModel->count ?? 0,
            ];
        }

        $meta = new PageMeta($title, $pagination->getCurrentPage());
        return new ViewResponse(
            '@album/public/users.twig',
            [
                'title'      => $meta->title,
                'page_title' => $title,
                'filters'    => [
                    'all'   => ['name' => __('All'), 'url' => '/album/users', 'active' => $filter === null],
                    'boys'  => ['name' => __('Guys'), 'url' => '/album/users/boys', 'active' => $filter === 'boys'],
                    'girls' => ['name' => __('Girls'), 'url' => '/album/users/girls', 'active' => $filter === 'girls'],
                ],
                'users'      => $users,
                'total'      => $total,
                'pagination' => $pagination->hasPages() ? $pagination->render() : null,
            ]
        );
    }
}
