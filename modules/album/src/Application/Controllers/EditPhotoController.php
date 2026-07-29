<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\UseCases\EditPhotoUseCase;
use Johncms\Modules\Album\Application\UseCases\GetEditPhotoContextUseCase;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class EditPhotoController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private GetEditPhotoContextUseCase $getContextUseCase,
        private EditPhotoUseCase $editPhotoUseCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function form(int $img): Response
    {
        $photo = $this->resolveContext($img);
        if ($photo instanceof Response) {
            return $photo;
        }

        return $this->renderForm($photo, $photo->description);
    }

    public function save(Request $request, int $img): Response
    {
        $photo = $this->resolveContext($img);
        if ($photo instanceof Response) {
            return $photo;
        }

        $this->editPhotoUseCase->execute($photo, $request->body('description', ''));

        return new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Edit image'),
                    'type'          => 'alert-success',
                    'message'       => __('Image successfully changed'),
                    'back_url'      => '/album/' . $photo->album_id,
                    'back_url_name' => __('Continue'),
                ]
            )
        );
    }

    private function renderForm(AlbumPhoto $photo, string $description): Response
    {
        $title = __('Edit image');

        $this->navChain->add(__('Albums'), '/album');
        $userAlbumsLabel = $photo->user_id === $this->currentUser->id ? __('Your albums') : __('User albums');
        $this->navChain->add($userAlbumsLabel, '/album/user/' . $photo->user_id);
        $this->navChain->add($photo->album->name ?? '', '/album/' . $photo->album_id);
        $this->navChain->add(__('Photo'), '/album/photo/' . $photo->id);
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return new Response(
            $this->render->render(
                'album::edit_photo',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => [
                        'action_url'  => '/album/photo/' . $photo->id . '/edit',
                        'back_url'    => '/album/' . $photo->album_id,
                        'image_url'   => pathToUrl(UPLOAD_PATH . 'users/album/' . $photo->user_id . '/' . $photo->tmb_name),
                        'description' => $description,
                    ],
                ]
            )
        );
    }

    /**
     * Resolve the photo with the access guard, or a rendered error page (with the proper HTTP status set).
     */
    private function resolveContext(int $img): AlbumPhoto|Response
    {
        try {
            return $this->getContextUseCase->execute($img);
        } catch (AlbumPhotoNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (AlbumEditForbiddenException $e) {
            return $this->renderError($e->getMessage(), 403);
        }
    }

    private function renderError(string $message, int $status = 200): Response
    {
        return new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'    => __('Edit image'),
                    'type'     => 'alert-danger',
                    'message'  => $message,
                    'back_url' => '/album',
                ]
            ),
            $status
        );
    }
}
