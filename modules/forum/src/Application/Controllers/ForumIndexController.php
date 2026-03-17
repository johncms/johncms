<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\UseCases\ViewForumIndexUseCase;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;

final readonly class ForumIndexController
{
    public function __construct(
        private Render $render,
        private Tools $tools,
        private ViewForumIndexUseCase $viewForumIndexUseCase,
    ) {
    }

    public function __invoke(): string
    {
        /** @var \Johncms\Counters $counters */
        $counters = di('counters');
        $result = $this->viewForumIndexUseCase->execute();

        $this->render->addData(
            [
                'keywords'    => $result->keywords,
                'description' => $result->description,
            ]
        );

        return $this->render->render(
            'forum::index',
            [
                'title'        => __('Forum'),
                'page_title'   => __('Forum'),
                'sections'     => $result->sections,
                'online'       => [
                    'online_u' => $result->onlineUsers,
                    'online_g' => $result->onlineGuests,
                ],
                'files_count'  => $result->showFileCounters
                    ? $this->tools->formatNumber($result->filesCount)
                    : 0,
                'unread_count' => $this->tools->formatNumber($counters->forumUnreadCount()),
            ]
        );
    }
}
