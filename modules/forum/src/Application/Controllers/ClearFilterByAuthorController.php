<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\FilterByAuthorWrongDataException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\UseCases\ClearFilterByAuthorUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetFilterByAuthorContextUseCase;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class ClearFilterByAuthorController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private GetFilterByAuthorContextUseCase $contextUseCase,
        private ClearFilterByAuthorUseCase $clearFilterByAuthorUseCase,
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

        $start = max(0, (int) $this->request->getQuery('start', 0));

        $validator = new Validator(
            ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        if (! $validator->isValid()) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Filter by author'),
                    'page_title'    => __('Filter by author'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/?type=topic&id=' . $id . '&amp;start=' . $start,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        try {
            $topic = $this->contextUseCase->execute($id);
        } catch (FilterByAuthorWrongDataException) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Filter by author'),
                    'page_title'    => __('Filter by author'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $this->clearFilterByAuthorUseCase->execute();

        redirect('/forum/?type=topic&id=' . $topic->id);
    }
}
