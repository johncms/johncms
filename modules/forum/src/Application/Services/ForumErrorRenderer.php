<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumException;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\Response;

final class ForumErrorRenderer
{
    /**
     * The failure of an action as a page. Pages still on Plates use render() below; it goes away
     * with the last of them.
     *
     * @param array<string, mixed> $overrides
     */
    public function viewResponse(ForumException $exception, array $overrides = []): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            $this->forException($exception, $overrides),
            $exception->getErrorCode()->httpStatus()
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function render(Render $render, ForumException $exception, array $overrides = []): Response
    {
        $payload = $this->forException($exception, $overrides);

        return new Response(
            $render->render('system::pages/result', $payload),
            $exception->getErrorCode()->httpStatus()
        );
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
