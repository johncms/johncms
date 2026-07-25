<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Album\Application\Exceptions\AlbumAccessDeniedException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPasswordRequiredException;
use Johncms\Modules\Album\Application\UseCases\GetAlbumViewUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class ShowAlbumController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetAlbumViewUseCase $useCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(int $al): string
    {
        $submittedPassword = $this->request->body('password');

        try {
            $pagination = $this->paginationFactory->create($this->useCase->count($al, $submittedPassword));

            if ($this->request->getMethod() !== 'POST') {
                $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
                if ($redirectUrl !== null) {
                    redirect($redirectUrl);
                }
            }

            $result = $this->useCase->getPage(
                $al,
                $pagination->getPerPage(),
                $pagination->getOffset(),
                $submittedPassword
            );
        } catch (AlbumNotFoundException) {
            return $this->renderResult(__('Wrong data'));
        } catch (AlbumAccessDeniedException $e) {
            return $this->renderResult(
                __('Access denied'),
                '/album/user/' . $e->ownerId,
                __('Album List'),
            );
        } catch (AlbumPasswordRequiredException $e) {
            return $this->renderPasswordForm($al, $e);
        }

        $title = __('Albums');
        $this->navChain->add($title, '/album');
        $userAlbumsLabel = $result->ownerId === $this->currentUser->id ? __('Your albums') : __('User albums');
        $this->navChain->add($userAlbumsLabel, '/album/user/' . $result->ownerId);
        $this->navChain->add($result->albumName);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'album::show',
            [
                'photos'        => $result->photos,
                'total'         => $pagination->getTotal(),
                'per_page'      => $pagination->getPerPage(),
                'has_add_photo' => $result->hasAddPhoto,
                'upload_url'    => '/album/' . $result->albumId . '/upload',
                'pagination'    => $pagination->render(),
            ]
        );
    }

    private function renderPasswordForm(int $albumId, AlbumPasswordRequiredException $e): string
    {
        $title = __('Albums');
        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'album::enter_password',
            [
                'action_url'    => '/album/' . $albumId,
                'back_url'      => '/album/user/' . $e->ownerId,
                'error_message' => $e->incorrectPassword ? __('Incorrect Password') : '',
            ]
        );
    }

    private function renderResult(string $message, string $backUrl = '', string $backUrlName = ''): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Albums'),
                'type'          => 'alert-danger',
                'message'       => $message,
                'back_url'      => $backUrl,
                'back_url_name' => $backUrlName,
            ]
        );
    }
}
