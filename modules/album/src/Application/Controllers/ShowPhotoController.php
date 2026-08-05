<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Album\Application\Exceptions\AlbumAccessDeniedException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPasswordRequiredException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\UseCases\GetPhotoViewUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;

final readonly class ShowPhotoController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private User $currentUser,
        private GetPhotoViewUseCase $useCase,
        private PaginationFactory $paginationFactory,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(Request $request, int $img): ViewResponse
    {
        $submittedPassword = $request->body('password');
        $page = $request->query->has('page') ? max(1, $request->queryInt('page')) : null;
        $addToProfile = $request->query->has('profile');

        try {
            $result = $this->useCase->execute($img, $page, $submittedPassword, $addToProfile);
        } catch (AlbumPhotoNotFoundException) {
            return $this->renderResult(__('Wrong data'));
        } catch (AlbumAccessDeniedException $e) {
            return $this->renderResult(
                __('Access denied'),
                '/album/user/' . $e->ownerId,
                __('Album List'),
            );
        } catch (AlbumPasswordRequiredException $e) {
            return $this->renderPasswordForm($e);
        }

        $title = __('View photo');
        $this->navChain->add(__('Albums'), '/album');
        $userAlbumsLabel = $result->ownerId === $this->currentUser->id ? __('Your albums') : __('User albums');
        $this->navChain->add($userAlbumsLabel, '/album/user/' . $result->ownerId);
        if ($result->photo !== null) {
            $this->navChain->add($result->photo->albumName, '/album/' . $result->albumId);
        }
        $this->navChain->add($title);

        // One photo per page: the current page is the photo's 1-based position in the album.
        $pagination = $this->paginationFactory->create($result->total, 1, 'page', $result->offset + 1);

        return new ViewResponse(
            '@album/public/show-one.twig',
            [
                'title'           => $title,
                'page_title'      => $title,
                'photo'           => $result->photo,
                'total'           => $result->total,
                'success_message' => $result->successMessage,
                'album_list_url'  => '/album/user/' . $result->ownerId,
                'album_url'       => '/album/' . $result->albumId,
                'pagination'      => $pagination->hasPages() ? $pagination->render() : null,
            ]
        );
    }

    private function renderPasswordForm(AlbumPasswordRequiredException $e): ViewResponse
    {
        $title = __('Albums');
        return new ViewResponse(
            '@album/public/enter-password.twig',
            [
                'title'         => $title,
                'page_title'    => $title,
                'action_url'    => '/album/' . $e->albumId,
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
                'title'         => __('View photo'),
                'type'          => 'alert-danger',
                'message'       => $message,
                'back_url'      => $backUrl,
                'back_url_name' => $backUrlName,
            ]
        );
    }
}
