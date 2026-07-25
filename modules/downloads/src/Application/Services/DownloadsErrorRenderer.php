<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Services;

use Johncms\Modules\Downloads\Application\Exceptions\DownloadsException;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\Response;

final class DownloadsErrorRenderer
{
    public function render(Render $render, DownloadsException $exception): Response
    {
        return new Response(
            $render->render('system::pages/result', [
                'title'   => $exception->getErrorCode()->title(),
                'type'    => 'alert-danger',
                'message' => $exception->getErrorCode()->message(),
            ]),
            $exception->getErrorCode()->httpStatus()
        );
    }
}
