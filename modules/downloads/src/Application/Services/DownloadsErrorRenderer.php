<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Services;

use Johncms\Modules\Downloads\Application\Exceptions\DownloadsException;
use Johncms\View\RendererInterface;
use Symfony\Component\HttpFoundation\Response;

final class DownloadsErrorRenderer
{
    public function response(RendererInterface $renderer, DownloadsException $exception): Response
    {
        return new Response(
            $renderer->render('@theme/pages/result.twig', [
                'title'   => $exception->getErrorCode()->title(),
                'type'    => 'alert-danger',
                'message' => $exception->getErrorCode()->message(),
            ]),
            $exception->getErrorCode()->httpStatus()
        );
    }
}
