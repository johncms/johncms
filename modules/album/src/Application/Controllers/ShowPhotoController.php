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
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class ShowPhotoController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private GetPhotoViewUseCase $useCase,
        private PaginationFactory $paginationFactory,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(Request $request, int $img): string
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

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        // One photo per page: the current page is the photo's 1-based position in the album.
        $pagination = $this->paginationFactory->create($result->total, 1, 'page', $result->offset + 1);

        return $this->render->render(
            'album::show_one',
            [
                'photo'           => $result->photo,
                'total'           => $result->total,
                'per_page'        => 1,
                'success_message' => $result->successMessage,
                'album_list_url'  => '/album/user/' . $result->ownerId,
                'album_url'       => '/album/' . $result->albumId,
                'pagination'      => $pagination->render(),
            ]
        );
    }

    private function renderPasswordForm(AlbumPasswordRequiredException $e): string
    {
        $title = __('Albums');
        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'album::enter_password',
            [
                'action_url'    => '/album/' . $e->albumId,
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
                'title'         => __('View photo'),
                'type'          => 'alert-danger',
                'message'       => $message,
                'back_url'      => $backUrl,
                'back_url_name' => $backUrlName,
            ]
        );
    }
}
