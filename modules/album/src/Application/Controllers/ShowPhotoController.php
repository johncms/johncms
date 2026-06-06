<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Album\Application\Exceptions\AlbumAccessDeniedException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPasswordRequiredException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\UseCases\GetPhotoViewUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class ShowPhotoController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private GetPhotoViewUseCase $useCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(int $img): string
    {
        $submittedPassword = $this->request->getPost('password');
        $pageRaw = $this->request->getQuery('page');
        $page = $pageRaw !== null ? max(1, (int) $pageRaw) : null;
        $addToProfile = $this->request->getQuery('profile') !== null;

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
        if ($result->photo !== null) {
            $this->navChain->add($result->photo->albumName, '/album/' . $result->albumId);
        }
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'album::show_one',
            [
                'photo'           => $result->photo,
                'total'           => $result->total,
                'per_page'        => 1,
                'success_message' => $result->successMessage,
                'album_list_url'  => '/album/user/' . $result->ownerId,
                'album_url'       => '/album/' . $result->albumId,
                'pagination'      => $this->tools->displayPagination(
                    '/album/photo/' . $img . '?',
                    $result->offset,
                    $result->total,
                    1
                ),
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
