<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Album\Application\Exceptions\AlbumAccessDeniedException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPasswordRequiredException;
use Johncms\Modules\Album\Application\UseCases\GetAlbumViewUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class ShowAlbumController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private GetAlbumViewUseCase $useCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(int $al): string
    {
        $submittedPassword = $this->request->getPost('password');
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $perPage = $this->currentUser->config->kmess;

        try {
            $result = $this->useCase->execute($al, $page, $perPage, $submittedPassword);
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
        $this->navChain->add($this->tools->checkout($result->albumName), '/album/' . $result->albumId);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'album::show',
            [
                'photos'       => $result->photos,
                'total'        => $result->total,
                'per_page'     => $perPage,
                'has_add_photo' => $result->hasAddPhoto,
                'upload_url'   => '/album/image_upload?al=' . $result->albumId . '&user=' . $result->ownerId,
                'pagination'   => $this->tools->displayPagination(
                    '/album/' . $result->albumId . '?',
                    ($page - 1) * $perPage,
                    $result->total,
                    $perPage
                ),
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
