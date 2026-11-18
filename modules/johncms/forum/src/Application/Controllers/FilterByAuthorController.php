<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\ViewFilterByAuthorUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;

final readonly class FilterByAuthorController
{
    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewFilterByAuthorUseCase $viewFilterByAuthorUseCase,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $page = max(1, $request->queryInt('page', 1));

        try {
            $context = $this->viewFilterByAuthorUseCase->execute($id);
        } catch (ForumValidationException | ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->viewResponse(
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

        $backUrl = $context->topic->url . ($page > 1 ? '?page=' . $page : '');
        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs($context->topic->section_id, $context->topic->name, $backUrl);
        $this->navChain->add(__('Filter by author'));

        return new ViewResponse(
            '@forum/public/filter-by-author.twig',
            [
                'title'               => __('Filter by author'),
                'page_title'          => __('Filter by author'),
                'back_url'            => $backUrl,
                'topic_name'          => $context->topic->name,
                'list'                => $context->authors,
                'selected_user_ids'   => $selectedUsers,
                'set_filter_action'   => '/forum/filter/' . $context->topic->id . '/set/' . ($page > 1 ? '?page=' . $page : ''),
                'clear_filter_action' => '/forum/filter/' . $context->topic->id . '/clear/' . ($page > 1 ? '?page=' . $page : ''),
            ]
        );
    }
}
