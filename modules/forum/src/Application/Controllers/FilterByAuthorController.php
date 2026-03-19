<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\FilterByAuthorWrongDataException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewFilterByAuthorUseCase;
use Johncms\NavChain;
use Johncms\Security\Csrf;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\View\Render;

final readonly class FilterByAuthorController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Session $session,
        private NavChain $navChain,
        private Csrf $csrf,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private ViewFilterByAuthorUseCase $viewFilterByAuthorUseCase,
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

        try {
            $context = $this->viewFilterByAuthorUseCase->execute($id);
        } catch (FilterByAuthorWrongDataException) {
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

        $selectedUsers = [];
        if ((int) $this->session->get('fsort_id', 0) === $context->topic->id) {
            $selectedUsers = $this->session->get('fsort_users', []);
            if (! is_array($selectedUsers)) {
                $selectedUsers = [];
            } else {
                $selectedUsers = array_map('intval', $selectedUsers);
            }
        }

        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add(htmlspecialchars_decode($context->topic->name), $context->topic->url);
        $this->navChain->add(__('Filter by author'));

        return $this->render->render(
            'forum::filter_by_author',
            [
                'title'               => __('Filter by author'),
                'page_title'          => __('Filter by author'),
                'id'                  => $context->topic->id,
                'start'               => $start,
                'back_url'            => $context->topic->url . ($start > 0 ? '?start=' . $start : ''),
                'total'               => count($context->authors),
                'list'                => $context->authors,
                'topic'               => $context->topic,
                'saved'               => false,
                'selected_user_ids'   => $selectedUsers,
                'set_filter_action'   => '/forum/filter/' . $context->topic->id . '/set/?start=' . $start,
                'clear_filter_action' => '/forum/filter/' . $context->topic->id . '/clear/?start=' . $start,
                'csrf_token'          => $this->csrf->getToken(),
            ]
        );
    }
}
