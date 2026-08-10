<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Application\UseCases\GetSortAlbumContextUseCase;
use Johncms\Modules\Album\Application\UseCases\MoveAlbumUseCase;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class SortAlbumController
{
    public function __construct(
        private GetSortAlbumContextUseCase $getContextUseCase,
        private MoveAlbumUseCase $moveAlbumUseCase,
    ) {
    }

    public function moveUp(int $al): ViewResponse
    {
        $album = $this->resolveContext($al);
        if ($album instanceof ViewResponse) {
            return $album;
        }

        $this->moveAlbumUseCase->moveUp($album);

        redirect('/album/user/' . $album->user_id);
    }

    public function moveDown(int $al): ViewResponse
    {
        $album = $this->resolveContext($al);
        if ($album instanceof ViewResponse) {
            return $album;
        }

        $this->moveAlbumUseCase->moveDown($album);

        redirect('/album/user/' . $album->user_id);
    }

    /**
     * Resolve the album with the access guard, or a rendered error page (with the proper HTTP status set).
     */
    private function resolveContext(int $al): Album|ViewResponse
    {
        try {
            return $this->getContextUseCase->execute($al);
        } catch (AlbumNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (AlbumEditForbiddenException $e) {
            return $this->renderError($e->getMessage(), 403);
        }
    }

    private function renderError(string $message, int $status = 200): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'   => __('Albums'),
                'type'    => 'alert-danger',
                'message' => $message,
            ],
            $status
        );
    }
}
