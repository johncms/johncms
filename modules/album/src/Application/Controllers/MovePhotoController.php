<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\UseCases\GetMovePhotoContextUseCase;
use Johncms\Modules\Album\Application\UseCases\MovePhotoUseCase;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class MovePhotoController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private AlbumRepositoryInterface $albumRepository,
        private GetMovePhotoContextUseCase $getContextUseCase,
        private MovePhotoUseCase $movePhotoUseCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function form(int $img): Response
    {
        $photo = $this->resolveContext($img);
        if ($photo instanceof Response) {
            return $photo;
        }

        $targets = $this->albumRepository->getUserAlbumsExcept($photo->user_id, $photo->album_id);
        if ($targets->isEmpty()) {
            return new Response(
                $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Move image'),
                        'type'          => 'alert-info',
                        'message'       => __('You must create at least one additional album in order to move the image'),
                        'back_url'      => '/album/user/' . $photo->user_id,
                        'back_url_name' => __('Continue'),
                    ]
                )
            );
        }

        $albums = [];
        foreach ($targets as $album) {
            $albums[] = [
                'id'   => $album->id,
                'name' => $album->name,
            ];
        }

        $title = __('Move image');
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
                'album::move_photo',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => [
                        'action_url' => '/album/photo/' . $photo->id . '/move',
                        'back_url'   => '/album/' . $photo->album_id,
                        'albums'     => $albums,
                    ],
                ]
            )
        );
    }

    public function move(int $img): Response
    {
        $photo = $this->resolveContext($img);
        if ($photo instanceof Response) {
            return $photo;
        }

        $targetAlbumId = $this->request->bodyInt('al');

        try {
            $albumId = $this->movePhotoUseCase->execute($photo, $targetAlbumId);
        } catch (AlbumNotFoundException $e) {
            return $this->renderError($e->getMessage());
        }

        return new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Move image'),
                    'type'          => 'alert-success',
                    'message'       => __('Image successfully moved to the selected album'),
                    'back_url'      => '/album/' . $albumId,
                    'back_url_name' => __('Continue'),
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
                    'title'    => __('Move image'),
                    'type'     => 'alert-danger',
                    'message'  => $message,
                    'back_url' => '/album',
                ]
            ),
            $status
        );
    }
}
