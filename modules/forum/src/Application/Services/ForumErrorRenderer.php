<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Modules\Forum\Application\Exceptions\ForumException;
use Johncms\System\View\Render;

final class ForumErrorRenderer
{
    /**
     * @param array<string, mixed> $overrides
     */
    public function render(Render $render, ForumException $exception, array $overrides = []): string
    {
        $payload = $this->forException($exception, $overrides);
        http_response_code($exception->getErrorCode()->httpStatus());

        return $render->render('system::pages/result', $payload);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public function forException(ForumException $exception, array $overrides = []): array
    {
        $payload = [
            'title'      => $exception->getErrorCode()->title(),
            'type'       => 'alert-danger',
            'message'    => $exception->getErrorCode()->message(),
            'error_code' => $exception->getErrorCode()->value,
        ];

        return array_replace($payload, $overrides);
    }
}
