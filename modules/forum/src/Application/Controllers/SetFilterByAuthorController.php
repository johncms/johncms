<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetFilterByAuthorContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\SetFilterByAuthorUseCase;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class SetFilterByAuthorController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetFilterByAuthorContextUseCase $contextUseCase,
        private SetFilterByAuthorUseCase $setFilterByAuthorUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render($this->render, $exception);
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
                    'back_url'      => '/forum/filter/' . $id . '/?start=' . $start,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        try {
            $topic = $this->contextUseCase->execute($id);
        } catch (ForumValidationException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
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

        $users = $this->request->getPost('users', []);
        if (! is_array($users) || $users === []) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Filter by author'),
                    'page_title'    => __('Filter by author'),
                    'type'          => 'alert-danger',
                    'message'       => __('You have not selected any author'),
                    'back_url'      => '/forum/filter/' . $topic->id . '/?start=' . $start,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $this->setFilterByAuthorUseCase->execute($topic->id, $users);

        redirect($topic->url);
    }
}
