<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\UseCases\ViewTopUsersUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class TopUsersController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private ViewTopUsersUseCase $useCase,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(): string
    {
        $page = max(1, (int) $this->request->getQuery('page', 1));

        $result = $this->useCase->execute($page, $this->currentUser->config->kmess);

        $users = [];
        foreach ($result->users as $userModel) {
            $users[] = [
                'id'                      => $userModel->id,
                'name'                    => $userModel->name,
                'user_is_online'          => $userModel->is_online,
                'user_profile_link'       => ($this->currentUser->isValid() && $this->currentUser->id !== $userModel->id) ? $userModel->profile_url : '',
                'files_link'              => '<a href="/downloads/?act=user_files&amp;id=' . $userModel->id . '">' . __('User Files') . ': ' . $userModel->files_count . '</a>',
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

        $this->render->addData([
            'title'       => $this->buildDocumentTitle($documentTitle, $page),
            'page_title'  => $pageTitle,
            'description' => $this->buildDescription($documentTitle, $page),
        ]);

        return $this->render->render(
            'downloads::top_users',
            [
                'users'      => $users,
                'total'      => $result->users->total(),
                'pagination' => $this->tools->displayPagination(
                    '/downloads/top-users/?',
                    ($page - 1) * $this->currentUser->config->kmess,
                    $result->users->total(),
                    $this->currentUser->config->kmess
                ),
                'urls'       => ['downloads' => '/downloads/'],
            ]
        );
    }

    private function buildDocumentTitle(string $title, int $page): string
    {
        if ($page <= 1) {
            return $title;
        }

        return $title . ' — ' . d__('system', 'Page') . ' ' . $page;
    }

    private function buildDescription(string $description, int $page): string
    {
        if ($page <= 1 || $description === '') {
            return $description;
        }

        return $description . ' — ' . d__('system', 'Page') . ' ' . $page;
    }
}
