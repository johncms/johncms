<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Comments\Application\CommentsService;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Library\Application\Comments\LibraryCommentSubject;
use Johncms\NavChain;

/**
 * The newest comments on the articles, each as in its discussion and leading to it.
 */
final readonly class LatestCommentsController
{
    private const int LIMIT = 20;

    public function __construct(
        private NavChain $navChain,
        private CommentsService $comments,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Latest comments'));

        return new ViewResponse('@library/public/latest-comments.twig', [
            'title'      => __('Latest comments'),
            'page_title' => __('Latest comments'),
            'items'      => $this->comments->latest(LibraryCommentSubject::TYPE, self::LIMIT, 0),
        ]);
    }
}
