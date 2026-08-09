<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureMoveTopicAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetMoveTopicContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\MoveTopicUseCase;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Validator\Validator;

final readonly class MoveTopicController
{
    public function __construct(
        private ForumErrorRenderer $forumErrorRenderer,
        private EnsureMoveTopicAccessUseCase $accessUseCase,
        private GetMoveTopicContextUseCase $contextUseCase,
        private MoveTopicUseCase $moveTopicUseCase,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        try {
            $this->accessUseCase->execute();
            $other = filter_var($request->queryParam('other'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            $otherCategoryId = $other !== null && $other > 0 ? $other : null;
            $context = $this->contextUseCase->execute($id, $otherCategoryId);
        } catch (ForumAccessDeniedException | ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->viewResponse(
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
                return new ViewResponse(
                    '@theme/pages/result.twig',
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
            } catch (ForumNotFoundException $exception) {
                return $this->forumErrorRenderer->viewResponse(
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

        return new ViewResponse(
            '@forum/public/move-topic.twig',
            [
                'title'                     => __('Move topic'),
                'page_title'                => __('Move topic'),
                'current_section'           => $context->currentSection,
                'current_sections'          => $context->currentSections,
                'other_categories'          => $context->otherCategories,
                'other_category_url_prefix' => '/forum/move-topic/' . $id . '/?other=',
                'back_url'                  => $context->topic->url,
                'form_action'               => '/forum/move-topic/' . $id . '/',
            ]
        );
    }
}
