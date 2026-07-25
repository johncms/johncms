<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\ViewFilterByAuthorUseCase;
use Johncms\NavChain;
use Johncms\Security\Csrf;
use Johncms\Http\Request;
use Johncms\Http\Session;
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
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewFilterByAuthorUseCase $viewFilterByAuthorUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): string
    {
        $page = max(1, $this->request->queryInt('page', 1));

        try {
            $context = $this->viewFilterByAuthorUseCase->execute($id);
        } catch (ForumValidationException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'title'         => __('Filter by author'),
                    'page_title'    => __('Filter by author'),
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/filter/' . $id . '/' . ($page > 1 ? '?page=' . $page : ''),
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
        $this->navChain->add($context->topic->name, $context->topic->url);
        $this->navChain->add(__('Filter by author'));

        return $this->render->render(
            'forum::filter_by_author',
            [
                'title'               => __('Filter by author'),
                'page_title'          => __('Filter by author'),
                'id'                  => $context->topic->id,
                'back_url'            => $context->topic->url . ($page > 1 ? '?page=' . $page : ''),
                'total'               => count($context->authors),
                'list'                => $context->authors,
                'topic'               => $context->topic,
                'saved'               => false,
                'selected_user_ids'   => $selectedUsers,
                'set_filter_action'   => '/forum/filter/' . $context->topic->id . '/set/' . ($page > 1 ? '?page=' . $page : ''),
                'clear_filter_action' => '/forum/filter/' . $context->topic->id . '/clear/' . ($page > 1 ? '?page=' . $page : ''),
                'csrf_token'          => $this->csrf->getToken(),
            ]
        );
    }
}
