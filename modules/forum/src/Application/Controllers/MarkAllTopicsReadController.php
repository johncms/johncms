<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumUserAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\MarkAllTopicsReadUseCase;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;
use Symfony\Component\HttpFoundation\Response;

final readonly class MarkAllTopicsReadController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private MarkAllTopicsReadUseCase $markAllTopicsReadUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(Request $request): Response
    {
        try {
            $this->forumUserAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render($this->render, $exception);
        }

        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        if (! $validator->isValid()) {
            return new Response(
                $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Unread'),
                        'page_title'    => __('Unread'),
                        'type'          => 'alert-danger',
                        'message'       => __('Wrong data'),
                        'back_url'      => '/forum/unread/',
                        'back_url_name' => __('Back'),
                    ]
                )
            );
        }

        $this->markAllTopicsReadUseCase->execute();

        return new Response(
            $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Unread'),
                    'page_title'    => __('Unread'),
                    'type'          => 'alert-success',
                    'message'       => __('All topics marked as read'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Forum'),
                ]
            )
        );
    }
}
