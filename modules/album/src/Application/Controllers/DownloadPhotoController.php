<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Album\Application\Exceptions\AlbumAccessDeniedException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPasswordRequiredException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoFileMissingException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\UseCases\DownloadPhotoUseCase;
use Johncms\System\View\Render;

final readonly class DownloadPhotoController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private DownloadPhotoUseCase $useCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(int $img): string
    {
        try {
            $url = $this->useCase->execute($img);
        } catch (AlbumPhotoNotFoundException) {
            return $this->renderError(__('Wrong data'));
        } catch (AlbumAccessDeniedException | AlbumPasswordRequiredException) {
            return $this->renderError(__('Access forbidden'));
        } catch (AlbumPhotoFileMissingException) {
            return $this->renderError(__('File does not exist'));
        }

        http_response_code(302);
        header('Location: ' . $url);
        exit;
    }

    private function renderError(string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Albums'),
                'type'          => 'alert-danger',
                'message'       => $message,
                'back_url'      => '/album',
                'back_url_name' => __('Albums'),
            ]
        );
    }
}
