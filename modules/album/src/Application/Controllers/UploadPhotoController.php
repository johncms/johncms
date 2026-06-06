<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Application\Exceptions\ImageUploadException;
use Johncms\Modules\Album\Application\UseCases\GetUploadPhotoContextUseCase;
use Johncms\Modules\Album\Application\UseCases\UploadPhotoUseCase;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class UploadPhotoController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private GetUploadPhotoContextUseCase $getContextUseCase,
        private UploadPhotoUseCase $uploadPhotoUseCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function form(int $al): string
    {
        $album = $this->resolveContext($al);
        if (is_string($album)) {
            return $album;
        }

        return $this->renderForm($album, []);
    }

    public function upload(int $al): string
    {
        $album = $this->resolveContext($al);
        if (is_string($album)) {
            return $album;
        }

        $file = $this->request->getUploadedFiles()['imagefile'] ?? null;
        $description = (string) $this->request->getPost('description', '');

        try {
            if ($file === null) {
                throw new ImageUploadException(__('An error occurred'));
            }
            $this->uploadPhotoUseCase->execute($album, $file, $description);
        } catch (ImageUploadException $e) {
            return $this->renderForm($album, [$e->getMessage()]);
        }

        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Upload image'),
                'type'          => 'alert-success',
                'message'       => __('Image uploaded'),
                'back_url'      => '/album/' . $album->id,
                'back_url_name' => __('Continue'),
            ]
        );
    }

    /**
     * @param list<string> $errors
     */
    private function renderForm(Album $album, array $errors): string
    {
        $title = __('Upload image');

        $this->navChain->add(__('Albums'), '/album');
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
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
        );
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

    private function renderError(string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'    => __('Upload image'),
                'type'     => 'alert-danger',
                'message'  => $message,
                'back_url' => '/album',
            ]
        );
    }
}
