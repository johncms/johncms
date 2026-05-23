<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Http\PageMeta;
use Johncms\Modules\Downloads\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Downloads\Application\UseCases\ViewUserFilesUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class UserFilesController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private ViewUserFilesUseCase $useCase,
        private FilePresenter $filePresenter,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(int $id): string
    {
        $page = max(1, (int) $this->request->getQuery('page', 1));

        try {
            $result = $this->useCase->execute($id, $page, $this->currentUser->config->kmess);
        } catch (UserNotFoundException) {
            http_response_code(404);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Downloads'),
                    'type'          => 'alert-danger',
                    'message'       => __('User does not exists'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ]
            );
        }

        $profileUser = $result->user;

        $showUser = [
            'id'                      => $profileUser->id,
            'name'                    => $profileUser->name,
            'user_is_online'          => $profileUser->is_online,
            'user_profile_link'       => ($this->currentUser->isValid() && $this->currentUser->id !== $profileUser->id)
                ? $profileUser->profile_url
                : '',
            'search_ip_url'           => $profileUser->search_ip_url,
            'ip'                      => $profileUser->ip,
            'search_ip_via_proxy_url' => $profileUser->search_ip_via_proxy_url,
            'ip_via_proxy'            => $profileUser->ip_via_proxy,
            'browser'                 => $profileUser->browser,
        ];

        $files = [];
        foreach ($result->files as $file) {
            $files[] = $this->filePresenter->present($file);
        }

        $pageTitle = __('User Files');
        $documentTitle = $pageTitle . ' — ' . __('Downloads');

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add($pageTitle);

        $meta = new PageMeta($documentTitle, $page);
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        return $this->render->render(
            'downloads::files_user',
            [
                'show_user'  => $showUser,
                'files'      => $files,
                'total'      => $result->files->total(),
                'pagination' => $this->tools->displayPagination(
                    '/downloads/user-files/' . $id . '/?',
                    ($page - 1) * $this->currentUser->config->kmess,
                    $result->files->total(),
                    $this->currentUser->config->kmess
                ),
                'urls'       => ['downloads' => '/downloads/'],
            ]
        );
    }
}
