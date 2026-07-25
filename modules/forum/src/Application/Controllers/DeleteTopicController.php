<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\DeleteTopicUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetDeleteTopicContextUseCase;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteTopicController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private User $user,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetDeleteTopicContextUseCase $contextUseCase,
        private DeleteTopicUseCase $deleteTopicUseCase,
        private ForumSectionPathService $sectionPathService,
        private ForumTopicPathService $topicPathService,
    ) {
        $this->controllerContext->initModule('forum');
    }

    /**
     * @throws \Throwable
     */
    public function __invoke(int $id): Response
    {
        try {
            $topic = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'title'         => __('Curators'),
                    'page_title'    => __('Curators'),
                    'message'       => __('Topic has been deleted or does not exists'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        if ($this->request->hasBody('submit')) {
            $deleteMode = $this->request->bodyInt('del', 0);

            if ($deleteMode === 2 && $this->user->rights === 9) {
                $this->deleteTopicUseCase->deleteTopic($topic->id);
            } else {
                $this->deleteTopicUseCase->hideTopic($topic->id, $this->user->name);
            }

            redirect($this->sectionPathService->getSectionUrlById($topic->section_id) ?? '/forum/');
        }

        return new Response(
            $this->render->render(
                'forum::delete_topic',
                [
                    'title'           => __('Delete Topic'),
                    'page_title'      => __('Delete Topic'),
                    'id'              => $topic->id,
                    'back_url'        => $this->topicPathService->getTopicUrlById($topic->id) ?? '/forum/',
                    'can_hard_delete' => $this->user->rights === 9,
                    'delete_url'      => '/forum/delete-topic/' . $topic->id . '/',
                ]
            )
        );
    }
}
