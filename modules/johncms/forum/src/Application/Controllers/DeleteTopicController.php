<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;
use Johncms\Modules\Forum\Application\UseCases\DeleteTopicUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetDeleteTopicContextUseCase;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;

final readonly class DeleteTopicController
{
    public function __construct(
        private CurrentUser $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetDeleteTopicContextUseCase $contextUseCase,
        private DeleteTopicUseCase $deleteTopicUseCase,
        private ForumSectionPathService $sectionPathService,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function __invoke(Request $request, int $id): ViewResponse
    {
        try {
            $topic = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Delete Topic'),
                    'page_title'    => __('Delete Topic'),
                    'message'       => __('Topic has been deleted or does not exists'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        // Deleting for good is asked for explicitly; anything else hides the topic.
        if ($request->bodyInt('del', 1) === 2 && $this->accessChecker->allows(ForumPermissions::TOPIC_DESTROY)) {
            $this->deleteTopicUseCase->deleteTopic($topic->id);
        } else {
            $this->deleteTopicUseCase->hideTopic($topic->id, $this->currentUser->user()->name);
        }

        redirect($this->sectionPathService->getSectionUrlById($topic->section_id) ?? '/forum/');
    }
}
