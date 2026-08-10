<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\ClearFilterByAuthorUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetFilterByAuthorContextUseCase;
use Johncms\Http\Request;
use Johncms\Validator\Validator;

final readonly class ClearFilterByAuthorController
{
    public function __construct(
        private ForumErrorRenderer $forumErrorRenderer,
        private GetFilterByAuthorContextUseCase $contextUseCase,
        private ClearFilterByAuthorUseCase $clearFilterByAuthorUseCase,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $page = max(1, $request->queryInt('page', 1));

        try {
            $topic = $this->contextUseCase->execute($id);
        } catch (ForumValidationException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Filter by author'),
                    'page_title'    => __('Filter by author'),
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $this->clearFilterByAuthorUseCase->execute();

        redirect($topic->url);
    }
}
