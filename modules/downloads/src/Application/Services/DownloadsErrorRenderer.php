<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Services;

use Johncms\Modules\Downloads\Application\Exceptions\DownloadsException;
use Johncms\System\View\Render;

final class DownloadsErrorRenderer
{
    public function render(Render $render, DownloadsException $exception): string
    {
        http_response_code($exception->getErrorCode()->httpStatus());

        return $render->render('system::pages/result', [
            'title'   => $exception->getErrorCode()->title(),
            'type'    => 'alert-danger',
            'message' => $exception->getErrorCode()->message(),
        ]);
    }
}
