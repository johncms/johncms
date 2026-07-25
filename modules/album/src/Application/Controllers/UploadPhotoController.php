<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\UploadedFileMapper;
use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Application\Exceptions\ImageUploadException;
use Johncms\Modules\Album\Application\UseCases\GetUploadPhotoContextUseCase;
use Johncms\Modules\Album\Application\UseCases\UploadPhotoUseCase;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final readonly class UploadPhotoController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetUploadPhotoContextUseCase $getContextUseCase,
        private UploadPhotoUseCase $uploadPhotoUseCase,
        private UploadedFileMapper $uploadedFileMapper,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function form(int $al): Response
    {
        $album = $this->resolveContext($al);
        if ($album instanceof Response) {
            return $album;
        }

        return $this->renderForm($album, []);
    }

    public function upload(int $al): Response
    {
        $album = $this->resolveContext($al);
        if ($album instanceof Response) {
            return $album;
        }

        $uploaded = $this->request->files->get('imagefile');
        $description = $this->request->body('description', '');

        try {
            if (! $uploaded instanceof UploadedFile) {
                throw new ImageUploadException(__('An error occurred'));
            }
            $this->uploadPhotoUseCase->execute($album, $this->uploadedFileMapper->fromUploadedFile($uploaded), $description);
        } catch (ImageUploadException $e) {
            return $this->renderForm($album, [$e->getMessage()]);
        }

        return new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Upload image'),
                    'type'          => 'alert-success',
                    'message'       => __('Image uploaded'),
                    'back_url'      => '/album/' . $album->id,
                    'back_url_name' => __('Continue'),
                ]
            )
        );
    }

    /**
     * @param list<string> $errors
     */
    private function renderForm(Album $album, array $errors): Response
    {
        $title = __('Upload image');

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
                'album::add_photo',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => [
                        'action_url'    => '/album/' . $album->id . '/upload',
                        'back_url'      => '/album/' . $album->id,
                        'error_message' => $errors,
                    ],
                ]
            )
        );
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

    private function renderError(string $message, int $status = 200): Response
    {
        return new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'    => __('Upload image'),
                    'type'     => 'alert-danger',
                    'message'  => $message,
                    'back_url' => '/album',
                ]
            ),
            $status
        );
    }
}
