<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Comments\Application\CommentsService;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Downloads\Application\Comments\DownloadsCommentSubject;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\NavChain;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The latest comments of all the files, newest first, for a moderator to look through. Each one
 * is shown as in its discussion, with the same actions.
 */
final readonly class CommentsReviewController
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private NavChain $navChain,
        private CommentsService $comments,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(): ViewResponse|RedirectResponse
    {
        if (! config('johncms.mod_down_comm') && ! $this->accessChecker->allows(DownloadsPermissions::COMMENTS_ALWAYS_VIEW)) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Comments are disabled'),
                    'type'          => 'alert-danger',
                    'message'       => __('Comments are disabled'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $pagination = $this->paginationFactory->create($this->comments->countOfType(DownloadsCommentSubject::TYPE));
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            return new RedirectResponse($redirectUrl);
        }

        $pageTitle = __('Review comments');
        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add($pageTitle);
        $meta = new PageMeta($pageTitle . ' — ' . __('Downloads'), $pagination->getCurrentPage());

        return new ViewResponse(
            '@downloads/public/comments-review.twig',
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
                'items'       => $this->comments->latest(
                    DownloadsCommentSubject::TYPE,
                    $pagination->getPerPage(),
                    $pagination->getOffset()
                ),
                'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
            ]
        );
    }
}
