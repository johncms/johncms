<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Album\Application\Exceptions\AlbumAccessDeniedException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPasswordRequiredException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoFileMissingException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\UseCases\DownloadPhotoUseCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class DownloadPhotoController
{
    public function __construct(
        private DownloadPhotoUseCase $useCase,
    ) {
    }

    public function __invoke(int $img): Response|ViewResponse
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

        // The photo is served from a static URL, so a redirect is all that is needed.
        return new RedirectResponse($url, 302);
    }

    private function renderError(string $message): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
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
