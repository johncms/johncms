<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Downloads\Application\UseCases\ViewTopUsersUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Users\User;

final readonly class TopUsersController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private User $currentUser,
        private ViewTopUsersUseCase $useCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(): ViewResponse
    {
        $pagination = $this->paginationFactory->create($this->useCase->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->useCase->getPage($pagination->getPerPage(), $pagination->getOffset());

        $users = [];
        foreach ($result->users as $userModel) {
            $users[] = [
                'id'                      => $userModel->id,
                'name'                    => $userModel->name,
                'user_is_online'          => $userModel->is_online,
                'user_profile_link'       => ($this->currentUser->isValid() && $this->currentUser->id !== $userModel->id) ? $userModel->profile_url : '',
                'files_url'               => '/downloads/user-files/' . $userModel->id . '/',
                'files_count'             => $userModel->files_count,
                'search_ip_url'           => $userModel->search_ip_url,
                'ip'                      => $userModel->ip,
                'search_ip_via_proxy_url' => $userModel->search_ip_via_proxy_url,
                'ip_via_proxy'            => $userModel->ip_via_proxy,
                'browser'                 => $userModel->browser,
            ];
        }

        $pageTitle = __('Top Users');
        $documentTitle = $pageTitle . ' — ' . __('Downloads');

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add($pageTitle);

        $meta = new PageMeta($documentTitle, $pagination->getCurrentPage());

        return new ViewResponse(
            '@downloads/public/top-users.twig',
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
                'users'       => $users,
                'total'       => $pagination->getTotal(),
                'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
                'urls'        => ['downloads' => '/downloads/'],
            ]
        );
    }
}
