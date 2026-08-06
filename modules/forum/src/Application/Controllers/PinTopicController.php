<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\GetPinTopicContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\PinTopicUseCase;
use Johncms\Http\Request;

final readonly class PinTopicController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetPinTopicContextUseCase $contextUseCase,
        private PinTopicUseCase $pinTopicUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

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
        } catch (ForumNotFoundException) {
            pageNotFound();
        }

        $pin = $request->query->has('pin');
        $this->pinTopicUseCase->execute($topic->id, $pin);
        redirect($topic->url);
    }
}
