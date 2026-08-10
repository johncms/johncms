<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\UseCases\DeletePhotoUseCase;
use Johncms\Modules\Album\Application\UseCases\GetDeletePhotoContextUseCase;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeletePhotoController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private GetDeletePhotoContextUseCase $getContextUseCase,
        private DeletePhotoUseCase $deletePhotoUseCase,
    ) {
    }

    public function confirm(int $img): ViewResponse
    {
        $photo = $this->resolveContext($img);
        if ($photo instanceof ViewResponse) {
            return $photo;
        }

        $title = __('Delete image');

        $this->navChain->add(__('Albums'), '/album');
        $userAlbumsLabel = $photo->user_id === $this->currentUser->id ? __('Your albums') : __('User albums');
        $this->navChain->add($userAlbumsLabel, '/album/user/' . $photo->user_id);
        $this->navChain->add($photo->album->name ?? '', '/album/' . $photo->album_id);
        $this->navChain->add(__('Photo'), '/album/photo/' . $photo->id);
        $this->navChain->add($title);

        return new ViewResponse(
            '@album/public/confirm-delete.twig',
            [
                'title'       => $title,
                'page_title'  => $title,
                'message'     => __('Are you sure you want to delete this image?'),
                'form_action' => '/album/photo/' . $photo->id . '/delete',
                'back_url'    => '/album/' . $photo->album_id,
            ]
        );
    }

    public function delete(int $img): ViewResponse
    {
        $photo = $this->resolveContext($img);
        if ($photo instanceof ViewResponse) {
            return $photo;
        }

        $albumId = $photo->album_id;

        $this->deletePhotoUseCase->execute($photo);

        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'    => __('Delete image'),
                'type'     => 'alert-success',
                'message'  => __('Image successfully deleted'),
                'back_url' => '/album/' . $albumId,
            ]
        );
    }

    /**
     * Resolve the photo with the access guard, or a rendered error page (with the proper HTTP status set).
     */
    private function resolveContext(int $img): AlbumPhoto|ViewResponse
    {
        try {
            return $this->getContextUseCase->execute($img);
        } catch (AlbumPhotoNotFoundException $e) {
            return $this->renderError($e->getMessage(), 403);
        } catch (AlbumEditForbiddenException $e) {
            return $this->renderError($e->getMessage(), 403);
        }
    }

    private function renderError(string $message, int $status = 200): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'    => __('Delete image'),
                'type'     => 'alert-danger',
                'message'  => $message,
                'back_url' => '/album',
            ],
            $status
        );
    }
}
