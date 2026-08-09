<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;

final readonly class ForumPathController
{
    public function __construct(
        private ForumSectionPathService $sectionPathService,
        private ForumTopicPathService $topicPathService,
        private ForumSectionController $forumSectionController,
        private ForumTopicController $forumTopicController,
    ) {
    }

    public function __invoke(Request $request, string $sectionPath): ViewResponse
    {
        if ($this->sectionPathService->findSectionByPath($sectionPath) !== null) {
            return $this->forumSectionController->__invoke($request, $sectionPath);
        }

        if ($this->topicPathService->parseTopicPath('/forum/' . ltrim($sectionPath, '/')) !== null) {
            return $this->forumTopicController->__invoke($request, $sectionPath);
        }

        pageNotFound();
    }
}
