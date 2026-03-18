<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumSectionNotFoundException;
use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewForumSectionUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;

final readonly class ForumSectionController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Tools $tools,
        private NavChain $navChain,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private ViewForumSectionUseCase $viewForumSectionUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(string $sectionPath): string
    {
        unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);

        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->render->render(
                'system::pages/result',
                $this->forumAccessResponseBuilder->forException($exception)
            );
        }

        $page = max(1, (int) $this->request->getQuery('page', 1));

        try {
            $result = $this->viewForumSectionUseCase->execute($sectionPath, $page);
        } catch (ForumSectionNotFoundException) {
            pageNotFound();
        }

        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs($result->section->parent, $result->section->name, $result->section->url);

        $this->render->addData(
            [
                'canonical'   => $result->canonical,
                'keywords'    => $result->section->calculated_meta_keywords,
                'description' => $result->section->calculated_meta_description,
                'title'       => htmlspecialchars_decode($result->section->name),
                'page_title'  => htmlspecialchars_decode($result->section->name),
            ]
        );

        /** @var \Johncms\Counters $counters */
        $counters = di('counters');

        return $this->render->render(
            $result->template,
            array_merge(
                $result->viewData,
                [
                    'id'           => $result->section->id,
                    'online'       => [
                        'online_u' => $result->onlineUsers,
                        'online_g' => $result->onlineGuests,
                    ],
                    'files_count'  => config('forum')['settings']['file_counters']
                        ? $this->tools->formatNumber($result->filesCount)
                        : 0,
                    'unread_count' => $this->tools->formatNumber($counters->forumUnreadCount()),
                ]
            )
        );
    }
}
