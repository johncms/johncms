<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\MoveTopicNotFoundException;
use Johncms\Modules\Forum\Application\Exceptions\MoveTopicSectionNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureMoveTopicAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetMoveTopicContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\MoveTopicUseCase;
use Johncms\Security\Csrf;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class MoveTopicController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Csrf $csrf,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private EnsureMoveTopicAccessUseCase $accessUseCase,
        private GetMoveTopicContextUseCase $contextUseCase,
        private MoveTopicUseCase $moveTopicUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

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
            $other = $this->request->getQuery('other', null, FILTER_VALIDATE_INT);
            $otherCategoryId = $other !== null && $other > 0 ? $other : null;
            $context = $this->contextUseCase->execute($id, $otherCategoryId);
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
        } catch (MoveTopicNotFoundException) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Wrong data'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        if ($this->request->getPost('submit') !== null) {
            $targetSectionId = $this->request->getPost('razd', 0, FILTER_VALIDATE_INT);

            $validator = new Validator(
                ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
                ['csrf_token' => ['Csrf']]
            );

            if (! $validator->isValid() || $targetSectionId <= 0) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Wrong data'),
                        'type'          => 'alert-danger',
                        'message'       => __('Wrong data'),
                        'back_url'      => '/forum/',
                        'back_url_name' => __('Back'),
                    ]
                );
            }

            try {
                $this->moveTopicUseCase->execute($context->topic, $targetSectionId);
            } catch (MoveTopicSectionNotFoundException) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Wrong data'),
                        'type'          => 'alert-danger',
                        'message'       => __('Wrong data'),
                        'back_url'      => '/forum/',
                        'back_url_name' => __('Back'),
                    ]
                );
            }

            redirect('/forum/?type=topic&id=' . $context->topic->id);
        }

        return $this->render->render(
            'forum::move_topic',
            [
                'title'            => __('Move topic'),
                'page_title'       => __('Move topic'),
                'id'               => $id,
                'current_section'  => $context->currentSection,
                'current_sections' => $context->currentSections,
                'other_categories' => $context->otherCategories,
                'back_url'         => '/forum/?type=topic&id=' . $id,
                'form_action'      => '/forum/move-topic/' . $id . '/',
                'csrf_token'       => $this->csrf->getToken(),
            ]
        );
    }
}
