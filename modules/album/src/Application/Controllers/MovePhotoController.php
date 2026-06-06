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
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class MovePhotoController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private AlbumRepositoryInterface $albumRepository,
        private GetMovePhotoContextUseCase $getContextUseCase,
        private MovePhotoUseCase $movePhotoUseCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function form(int $img): string
    {
        $photo = $this->resolveContext($img);
        if (is_string($photo)) {
            return $photo;
        }

        $targets = $this->albumRepository->getUserAlbumsExcept($photo->user_id, $photo->album_id);
        if ($targets->isEmpty()) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Move image'),
                    'type'          => 'alert-info',
                    'message'       => __('You must create at least one additional album in order to move the image'),
                    'back_url'      => '/album/user/' . $photo->user_id,
                    'back_url_name' => __('Continue'),
                ]
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
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
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
        );
    }

    public function move(int $img): string
    {
        $photo = $this->resolveContext($img);
        if (is_string($photo)) {
            return $photo;
        }

        $targetAlbumId = (int) $this->request->getPost('al', 0, FILTER_VALIDATE_INT);

        try {
            $albumId = $this->movePhotoUseCase->execute($photo, $targetAlbumId);
        } catch (AlbumNotFoundException $e) {
            return $this->renderError($e->getMessage());
        }

        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Move image'),
                'type'          => 'alert-success',
                'message'       => __('Image successfully moved to the selected album'),
                'back_url'      => '/album/' . $albumId,
                'back_url_name' => __('Continue'),
            ]
        );
    }

    /**
     * Resolve the photo with the access guard, or a rendered error page (with the proper HTTP status set).
     *
     * @return AlbumPhoto|string
     */
    private function resolveContext(int $img): AlbumPhoto|string
    {
        try {
            return $this->getContextUseCase->execute($img);
        } catch (AlbumPhotoNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (AlbumEditForbiddenException $e) {
            http_response_code(403);
            return $this->renderError($e->getMessage());
        }
    }

    private function renderError(string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'    => __('Move image'),
                'type'     => 'alert-danger',
                'message'  => $message,
                'back_url' => '/album',
            ]
        );
    }
}
