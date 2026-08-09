<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Downloads\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Downloads\Application\UseCases\ViewUserFilesUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class UserFilesController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private ViewUserFilesUseCase $useCase,
        private FilePresenter $filePresenter,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(int $id): ViewResponse
    {
        try {
            $pagination = $this->paginationFactory->create($this->useCase->count($id));

            $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }

            $result = $this->useCase->getPage($id, $pagination->getPerPage(), $pagination->getOffset());
        } catch (UserNotFoundException) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Downloads'),
                    'type'          => 'alert-danger',
                    'message'       => __('User does not exists'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ],
                Response::HTTP_NOT_FOUND
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

        $meta = new PageMeta($documentTitle, $pagination->getCurrentPage());

        return new ViewResponse(
            '@downloads/public/user-files.twig',
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
                'show_user'   => $showUser,
                'files'       => $files,
                'total'       => $pagination->getTotal(),
                'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
                'urls'        => ['downloads' => '/downloads/'],
            ]
        );
    }
}
