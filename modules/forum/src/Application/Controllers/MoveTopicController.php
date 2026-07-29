<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureMoveTopicAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetMoveTopicContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\MoveTopicUseCase;
use Johncms\Security\Csrf;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;
use Symfony\Component\HttpFoundation\Response;

final readonly class MoveTopicController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Csrf $csrf,
        private ForumErrorRenderer $forumErrorRenderer,
        private EnsureMoveTopicAccessUseCase $accessUseCase,
        private GetMoveTopicContextUseCase $contextUseCase,
        private MoveTopicUseCase $moveTopicUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(Request $request, int $id): Response
    {
        try {
            $this->accessUseCase->execute();
            $other = filter_var($request->queryParam('other'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            $otherCategoryId = $other !== null && $other > 0 ? $other : null;
            $context = $this->contextUseCase->execute($id, $otherCategoryId);
        } catch (ForumAccessDeniedException | ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        if ($request->hasBody('submit')) {
            $targetSectionId = $request->bodyInt('razd');

            $validator = new Validator(
                ['csrf_token' => $request->body('csrf_token', '')],
                ['csrf_token' => ['Csrf']]
            );

            if (! $validator->isValid() || $targetSectionId <= 0) {
                return new Response(
                    $this->render->render(
                        'system::pages/result',
                        [
                            'title'         => __('Wrong data'),
                            'type'          => 'alert-danger',
                            'message'       => __('Wrong data'),
                            'back_url'      => '/forum/',
                            'back_url_name' => __('Back'),
                        ]
                    )
                );
            }

            try {
                $this->moveTopicUseCase->execute($context->topic, $targetSectionId);
            } catch (ForumNotFoundException $exception) {
                return $this->forumErrorRenderer->render(
                    $this->render,
                    $exception,
                    [
                        'title'         => __('Wrong data'),
                        'type'          => 'alert-danger',
                        'message'       => __('Wrong data'),
                        'back_url'      => '/forum/',
                        'back_url_name' => __('Back'),
                    ]
                );
            }

            redirect($context->topic->url);
        }

        return new Response(
            $this->render->render(
                'forum::move_topic',
                [
                    'title'            => __('Move topic'),
                    'page_title'       => __('Move topic'),
                    'id'               => $id,
                    'current_section'  => $context->currentSection,
                    'current_sections' => $context->currentSections,
                    'other_categories' => $context->otherCategories,
                    'back_url'         => $context->topic->url,
                    'form_action'      => '/forum/move-topic/' . $id . '/',
                    'csrf_token'       => $this->csrf->getToken(),
                ]
            )
        );
    }
}
