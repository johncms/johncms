<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\DeleteTopicNotFoundException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\DeleteTopicUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureDeleteTopicAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetDeleteTopicContextUseCase;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class DeleteTopicController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private User $user,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private EnsureDeleteTopicAccessUseCase $accessUseCase,
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
    public function __invoke(int $id): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->render->render(
                'system::pages/result',
                $this->forumAccessResponseBuilder->forException($exception)
            );
        }

        try {
            $this->accessUseCase->execute();
            $context = $this->contextUseCase->execute($id);
        } catch (AccessDeniedException) {
            http_response_code(403);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Access forbidden'),
                    'type'          => 'alert-danger',
                    'message'       => __('Access forbidden'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (DeleteTopicNotFoundException) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Curators'),
                    'page_title'    => __('Curators'),
                    'type'          => 'alert-danger',
                    'message'       => __('Topic has been deleted or does not exists'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        if ($this->request->getPost('submit') !== null) {
            $deleteMode = (int) $this->request->getPost('del', 0);

            if ($deleteMode === 2 && $this->user->rights === 9) {
                $this->deleteTopicUseCase->deleteTopic($context->topicId);
            } else {
                $this->deleteTopicUseCase->hideTopic($context->topicId, $this->user->name);
            }

            redirect($this->sectionPathService->getSectionUrlById($context->sectionId) ?? '/forum/');
        }

        return $this->render->render(
            'forum::delete_topic',
            [
                'title'           => __('Delete Topic'),
                'page_title'      => __('Delete Topic'),
                'id'              => $context->topicId,
                'back_url'        => $this->topicPathService->getTopicUrlById($context->topicId) ?? '/forum/',
                'can_hard_delete' => $this->user->rights === 9,
                'delete_url'      => '/forum/delete-topic/' . $context->topicId . '/',
            ]
        );
    }
}
