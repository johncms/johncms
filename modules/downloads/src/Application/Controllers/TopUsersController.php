<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Downloads\Application\UseCases\ViewTopUsersUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class TopUsersController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private ViewTopUsersUseCase $useCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(): string
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
                'files_link'              => '<a href="/downloads/user-files/' . $userModel->id . '/">' . __('User Files') . ': ' . $userModel->files_count . '</a>',
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
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        return $this->render->render(
            'downloads::top_users',
            [
                'users'      => $users,
                'total'      => $pagination->getTotal(),
                'pagination' => $pagination->render(),
                'urls'       => ['downloads' => '/downloads/'],
            ]
        );
    }
}
