<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Services\ForumLegacyRedirectResolver;
use Johncms\Modules\Forum\Application\UseCases\ViewForumIndexUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Utils\ShortNumberFormatter;

final readonly class ForumIndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private ForumLegacyRedirectResolver $legacyRedirectResolver,
        private ViewForumIndexUseCase $viewForumIndexUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(): string
    {
        $legacyRedirectUrl = $this->legacyRedirectResolver->resolve($this->request->query->all());
        if ($legacyRedirectUrl !== null) {
            redirect($legacyRedirectUrl, 301);
        }

        /** @var \Johncms\Counters $counters */
        $counters = di('counters');
        $result = $this->viewForumIndexUseCase->execute();

        $this->navChain->add(__('Forum'), '/forum/');

        $this->render->addData(
            [
                'canonical'   => (string) config('johncms.homeurl') . '/forum/',
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
                'files_count'  => $result->showFileCounters ? ShortNumberFormatter::format($result->filesCount) : 0,
                'unread_count' => ShortNumberFormatter::format($counters->forumUnreadCount()),
            ]
        );
    }
}
