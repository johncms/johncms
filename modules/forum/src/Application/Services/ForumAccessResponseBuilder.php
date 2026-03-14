<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAuthRequiredException;
use Johncms\Modules\Forum\Application\Exceptions\ForumClosedException;

final readonly class ForumAccessResponseBuilder
{
    /**
     * @return array{title: string, type: string, message: string}
     */
    public function forException(ForumAccessDeniedException $exception): array
    {
        if ($exception instanceof ForumClosedException) {
            return $this->forumClosed();
        }

        if ($exception instanceof ForumAuthRequiredException) {
            return $this->authRequired();
        }

        return $this->authRequired();
    }

    /**
     * @return array{title: string, type: string, message: string}
     */
    public function forumClosed(): array
    {
        return [
            'title'   => __('Forum'),
            'type'    => 'alert-danger',
            'message' => __('Forum is closed'),
        ];
    }

    /**
     * @return array{title: string, type: string, message: string}
     */
    public function authRequired(): array
    {
        return [
            'title'   => __('Forum'),
            'type'    => 'alert-danger',
            'message' => __('For registered users only'),
        ];
    }
}
