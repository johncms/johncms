<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Album\Application\Exceptions\AlbumAccessDeniedException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPasswordRequiredException;
use Johncms\Modules\Album\Application\UseCases\GetAlbumViewUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;

final readonly class ShowAlbumController
{
    public function __construct(
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private GetAlbumViewUseCase $useCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(Request $request, int $al): ViewResponse
    {
        $submittedPassword = $request->body('password');

        try {
            $pagination = $this->paginationFactory->create($this->useCase->count($al, $submittedPassword));

            if ($request->getMethod() !== 'POST') {
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
        $userAlbumsLabel = $result->ownerId === $this->currentUser->id() ? __('Your albums') : __('User albums');
        $this->navChain->add($userAlbumsLabel, '/album/user/' . $result->ownerId);
        $this->navChain->add($result->albumName);

        return new ViewResponse(
            '@album/public/show.twig',
            [
                'title'         => $title,
                'page_title'    => $title,
                'photos'        => $result->photos,
                'total'         => $pagination->getTotal(),
                'has_add_photo' => $result->hasAddPhoto,
                'upload_url'    => '/album/' . $result->albumId . '/upload',
                'pagination'    => $pagination->hasPages() ? $pagination->render() : null,
            ]
        );
    }

    private function renderPasswordForm(int $albumId, AlbumPasswordRequiredException $e): ViewResponse
    {
        $title = __('Albums');
        return new ViewResponse(
            '@album/public/enter-password.twig',
            [
                'title'         => $title,
                'page_title'    => $title,
                'action_url'    => '/album/' . $albumId,
                'back_url'      => '/album/user/' . $e->ownerId,
                'error_message' => $e->incorrectPassword ? __('Incorrect Password') : '',
            ]
        );
    }

    private function renderResult(string $message, string $backUrl = '', string $backUrlName = ''): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
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
