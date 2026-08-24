<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\Modules\Forum\Application\UseCases\ViewForumSectionUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Utils\ShortNumberFormatter;

final readonly class ForumSectionController
{
    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private ViewForumSectionUseCase $viewForumSectionUseCase,
        private PaginationFactory $paginationFactory,
    ) {
    }

    public function __invoke(Request $request, string $sectionPath): ViewResponse
    {
        $this->session->remove('fsort_id');
        $this->session->remove('fsort_users');

        $page = max(1, $request->queryInt('page', 1));

        try {
            $result = $this->viewForumSectionUseCase->execute($sectionPath, $page);
        } catch (ForumNotFoundException) {
            // ForumNotFoundException always maps to FORUM_NOT_FOUND (404), the same status
            // pageNotFound() answers with.
            pageNotFound();
        }

        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs($result->section->parent, $result->section->name, $result->section->url);

        /** @var \Johncms\Counters $counters */
        $counters = di('counters');

        $extra = [
            'canonical'    => $result->canonical,
            'keywords'     => $result->section->calculated_meta_keywords,
            'description'  => $result->section->calculated_meta_description,
            'title'        => $result->section->name,
            'page_title'   => $result->section->name,
            'id'           => $result->section->id,
            'online'       => [
                'online_u' => $result->onlineUsers,
                'online_g' => $result->onlineGuests,
            ],
            'files_count'  => config('forum')['settings']['file_counters']
                ? ShortNumberFormatter::format($result->filesCount)
                : 0,
            'unread_count' => ShortNumberFormatter::format($counters->forumUnreadCount()),
        ];

        if ($result->template === '@forum/public/topics.twig') {
            $pagination = $this->paginationFactory->create((int) $result->viewData['total'], null, 'page', $page);
            $extra['pagination'] = $pagination->hasPages() ? $pagination->render() : null;
        }

        return new ViewResponse($result->template, array_merge($result->viewData, $extra));
    }
}
