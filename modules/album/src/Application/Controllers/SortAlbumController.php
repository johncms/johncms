<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Application\UseCases\GetSortAlbumContextUseCase;
use Johncms\Modules\Album\Application\UseCases\MoveAlbumUseCase;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class SortAlbumController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private GetSortAlbumContextUseCase $getContextUseCase,
        private MoveAlbumUseCase $moveAlbumUseCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function moveUp(int $al): string
    {
        $album = $this->resolveContext($al);
        if (is_string($album)) {
            return $album;
        }
        if (! $this->isCsrfValid()) {
            return $this->renderError(__('Wrong data'));
        }

        $this->moveAlbumUseCase->moveUp($album);

        redirect('/album/user/' . $album->user_id);
    }

    public function moveDown(int $al): string
    {
        $album = $this->resolveContext($al);
        if (is_string($album)) {
            return $album;
        }
        if (! $this->isCsrfValid()) {
            return $this->renderError(__('Wrong data'));
        }

        $this->moveAlbumUseCase->moveDown($album);

        redirect('/album/user/' . $album->user_id);
    }

    /**
     * Resolve the album with the access guard, or a rendered error page (with the proper HTTP status set).
     *
     * @return Album|string
     */
    private function resolveContext(int $al): Album|string
    {
        try {
            return $this->getContextUseCase->execute($al);
        } catch (AlbumNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (AlbumEditForbiddenException $e) {
            http_response_code(403);
            return $this->renderError($e->getMessage());
        }
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => $this->request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderError(string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'   => __('Albums'),
                'type'    => 'alert-danger',
                'message' => $message,
            ]
        );
    }
}
