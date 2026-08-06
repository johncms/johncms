<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Modules\Library\Application\Services\Hashtags;
use Johncms\Utils\DateFormatterInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class TagsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private DateFormatterInterface $dateFormatter,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(Request $request): ViewResponse
    {
        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Tags'));

        $tag = trim($request->queryParam('tag', ''));

        if ($tag === '') {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'    => __('Tags'),
                    'type'     => 'alert-info',
                    'message'  => __('The list is empty'),
                    'back_url' => '/library/',
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $articleIds = (new Hashtags(0))->getAllTagStats($tag);

        if (! $articleIds) {
            return new ViewResponse('@theme/pages/result.twig', [
                'title'    => __('Tags'),
                'type'     => 'alert-info',
                'message'  => __('The list is empty'),
                'back_url' => '/library/',
            ]);
        }

        $total = count($articleIds);

        $pagination = $this->paginationFactory->create($total);
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $pageTitle     = __('Articles tagged: %s', $tag);
        $documentTitle = $pageTitle . ' — ' . __('Library');
        $meta          = new PageMeta($documentTitle, $pagination->getCurrentPage());

        $ids = array_slice($articleIds, $pagination->getOffset(), $pagination->getPerPage());

        $articles = LibraryText::query()
            ->selectRaw('`id`, `cat_id`, `slug`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `comm_count`, `comments`, SUBSTRING(`text`, 1, 200) as `text_preview`')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $list = [];
        foreach ($ids as $articleId) {
            $article = $articles->get($articleId);
            if ($article === null) {
                continue;
            }

            $list[] = [
                'id'          => $article->id,
                'url'         => $article->url,
                'name'        => $article->name,
                'text'        => strip_tags((string) $article->text_preview),
                'uploader_id' => $article->uploader_id,
                'uploader'    => $article->uploader,
                'date'        => $this->dateFormatter->format($article->time),
                'count_views' => $article->count_views,
                'comm_count'  => $article->comm_count,
                'comments'    => (bool) $article->comments,
                'tags'        => (new Hashtags($article->id))->getTagLinks(),
            ];
        }

        return new ViewResponse('@library/public/tags.twig', [
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
            'total'       => $total,
            'articles'    => $list,
            'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
        ]);
    }
}
