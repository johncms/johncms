<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\UseCases\DeletePhotoUseCase;
use Johncms\Modules\Album\Application\UseCases\GetDeletePhotoContextUseCase;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeletePhotoController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetDeletePhotoContextUseCase $getContextUseCase,
        private DeletePhotoUseCase $deletePhotoUseCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function confirm(int $img): Response
    {
        $photo = $this->resolveContext($img);
        if ($photo instanceof Response) {
            return $photo;
        }

        $title = __('Delete image');

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
                'album::confirm_delete',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => [
                        'message'     => __('Are you sure you want to delete this image?'),
                        'form_action' => '/album/photo/' . $photo->id . '/delete',
                        'back_url'    => '/album/' . $photo->album_id,
                    ],
                ]
            )
        );
    }

    public function delete(int $img): Response
    {
        $photo = $this->resolveContext($img);
        if ($photo instanceof Response) {
            return $photo;
        }
        if (! $this->isCsrfValid()) {
            return $this->renderError(__('Wrong data'));
        }

        $albumId = $photo->album_id;

        $this->deletePhotoUseCase->execute($photo);

        return new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'    => __('Delete image'),
                    'type'     => 'alert-success',
                    'message'  => __('Image successfully deleted'),
                    'back_url' => '/album/' . $albumId,
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
            return $this->renderError($e->getMessage(), 403);
        } catch (AlbumEditForbiddenException $e) {
            return $this->renderError($e->getMessage(), 403);
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

    private function renderError(string $message, int $status = 200): Response
    {
        return new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'    => __('Delete image'),
                    'type'     => 'alert-danger',
                    'message'  => $message,
                    'back_url' => '/album',
                ]
            ),
            $status
        );
    }
}
