<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Album\Application\DTO\DeleteAlbumContextDTO;
use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Application\UseCases\DeleteAlbumUseCase;
use Johncms\Modules\Album\Application\UseCases\GetDeleteAlbumContextUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteAlbumController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private GetDeleteAlbumContextUseCase $getContextUseCase,
        private DeleteAlbumUseCase $deleteAlbumUseCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function confirm(int $al): Response
    {
        $context = $this->resolveContext($al);
        if ($context instanceof Response) {
            return $context;
        }

        $album = $context->album;
        $title = __('Delete album:') . ' ' . $album->name;

        $this->navChain->add(__('Albums'), '/album');
        $userAlbumsLabel = $album->user_id === $this->currentUser->id ? __('Your albums') : __('User albums');
        $this->navChain->add($userAlbumsLabel, '/album/user/' . $album->user_id);
        $this->navChain->add($album->name, '/album/' . $album->id);
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return new Response(
            $this->render->render(
                'album::confirm_delete',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => [
                        'message'     => __('Are you sure you want to delete this album? If it contains photos, they also will be deleted.'),
                        'form_action' => '/album/' . $album->id . '/delete',
                        'back_url'    => '/album/user/' . $album->user_id,
                    ],
                ]
            )
        );
    }

    public function delete(Request $request, int $al): Response
    {
        $context = $this->resolveContext($al);
        if ($context instanceof Response) {
            return $context;
        }
        if (! $this->isCsrfValid($request)) {
            return $this->renderError(__('Wrong data'));
        }

        $album = $context->album;
        $ownerId = $album->user_id;

        $this->deleteAlbumUseCase->execute($album);

        return new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'    => __('Delete album'),
                    'type'     => 'alert-success',
                    'message'  => __('Album deleted'),
                    'back_url' => '/album/user/' . $ownerId,
                ]
            )
        );
    }

    /**
     * Resolve the delete context or, on failure, a rendered error page (with the proper HTTP status set).
     */
    private function resolveContext(int $al): DeleteAlbumContextDTO|Response
    {
        try {
            return $this->getContextUseCase->execute($al);
        } catch (AlbumNotFoundException $e) {
            return $this->renderError($e->getMessage(), 403);
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
