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
use Symfony\Component\HttpFoundation\Response;

final readonly class SortAlbumController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private GetSortAlbumContextUseCase $getContextUseCase,
        private MoveAlbumUseCase $moveAlbumUseCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function moveUp(Request $request, int $al): Response
    {
        $album = $this->resolveContext($al);
        if ($album instanceof Response) {
            return $album;
        }
        if (! $this->isCsrfValid($request)) {
            return $this->renderError(__('Wrong data'));
        }

        $this->moveAlbumUseCase->moveUp($album);

        redirect('/album/user/' . $album->user_id);
    }

    public function moveDown(Request $request, int $al): Response
    {
        $album = $this->resolveContext($al);
        if ($album instanceof Response) {
            return $album;
        }
        if (! $this->isCsrfValid($request)) {
            return $this->renderError(__('Wrong data'));
        }

        $this->moveAlbumUseCase->moveDown($album);

        redirect('/album/user/' . $album->user_id);
    }

    /**
     * Resolve the album with the access guard, or a rendered error page (with the proper HTTP status set).
     */
    private function resolveContext(int $al): Album|Response
    {
        try {
            return $this->getContextUseCase->execute($al);
        } catch (AlbumNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (AlbumEditForbiddenException $e) {
            return $this->renderError($e->getMessage(), 403);
        }
    }

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderError(string $message, int $status = 200): Response
    {
        return new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'   => __('Albums'),
                    'type'    => 'alert-danger',
                    'message' => $message,
                ]
            ),
            $status
        );
    }
}
