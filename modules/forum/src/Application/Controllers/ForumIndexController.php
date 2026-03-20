<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumLegacyRedirectResolver;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewForumIndexUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;

final readonly class ForumIndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ForumLegacyRedirectResolver $legacyRedirectResolver,
        private ViewForumIndexUseCase $viewForumIndexUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(): string
    {
        $legacyRedirectUrl = $this->legacyRedirectResolver->resolve($this->request->getQueryParams());
        if ($legacyRedirectUrl !== null) {
            http_response_code(301);
            header('Location: ' . $legacyRedirectUrl);
            exit;
        }

        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render($this->render, $exception);
        }

        /** @var \Johncms\Counters $counters */
        $counters = di('counters');
        $result = $this->viewForumIndexUseCase->execute();

        $this->navChain->add(__('Forum'), '/forum/');

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
                'files_count'  => $result->showFileCounters ? $this->tools->formatNumber($result->filesCount) : 0,
                'unread_count' => $this->tools->formatNumber($counters->forumUnreadCount()),
            ]
        );
    }
}
