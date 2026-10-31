<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Content;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\UseCases\GetCommentFeedUseCase;
use Johncms\NavChain;

/**
 * Every comment the visitors leave on the site — on files, articles, photos, news — in one list,
 * the newest first, so nobody has to go through the modules one by one. A comment leads to its
 * place on the site, where it is answered or removed.
 */
final readonly class CommentFeedController
{
    public function __construct(
        private NavChain $navChain,
        private GetCommentFeedUseCase $getCommentFeed,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $pagination = $this->paginationFactory->create($this->getCommentFeed->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $title = __('Comments');
        $this->navChain->add($title);
        $meta = new PageMeta($title, $pagination->getCurrentPage());

        return new ViewResponse('@admin/comments.twig', [
            'title'      => $meta->title,
            'page_title' => $title,
            'usr_menu'   => ['comments' => true],
            'comments'   => $this->getCommentFeed->getPage($pagination->getPerPage(), $pagination->getOffset()),
            'total'      => $pagination->getTotal(),
            'pagination' => $pagination->render(),
        ]);
    }
}
