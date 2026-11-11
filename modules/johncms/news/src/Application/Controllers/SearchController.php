<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Collections\Domain\Query\CollectionTextSearchDTO;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Modules\News\Application\DTO\NewsQueryDTO;
use Johncms\NavChain;

final readonly class SearchController
{
    public function __construct(
        private NewsReadServiceInterface $news,
        private NavChain $navChain,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function index(Request $request): ViewResponse
    {
        return $this->search($request, false);
    }

    public function byTags(Request $request): ViewResponse
    {
        return $this->search($request, true);
    }

    private function search(Request $request, bool $tags): ViewResponse
    {
        $this->navChain->add(d__('news', 'News'), '/news/');
        $title = $tags ? d__('news', 'Search by tags') : d__('news', 'Search');
        $this->navChain->add($title);
        $query = trim($request->queryParam($tags ? 'tag' : 'query'));
        $term = $tags ? $query : CollectionTextSearchDTO::normalizeTerm($query);
        $articles = [];
        $pagination = null;
        if ($term !== '') {
            $spec = new NewsQueryDTO(search: $tags ? '' : $term, tag: $tags ? $term : null);
            $pager = $this->paginationFactory->create($this->news->count($spec));
            $redirect = $this->paginationGuard->redirectUrl($pager);
            if ($redirect !== null) {
                redirect($redirect);
            }
            $articles = array_map(
                static fn ($entry): array => $entry->toTemplate(),
                $this->news->articles(new NewsQueryDTO(search: $spec->search, tag: $spec->tag, limit: $pager->getPerPage(), offset: $pager->getOffset()))
            );
            $pagination = $pager->hasPages() ? $pager->render(withRange: true) : null;
        }
        return new ViewResponse('@news/public/search.twig', array_replace($this->news->rootMetadata(), [
            'title' => $title, 'page_title' => $title, 'query' => $query, 'articles' => $articles, 'pagination' => $pagination,
            'form_action' => $tags ? '/news/search_tags/' : '/news/search/', 'form_field' => $tags ? 'tag' : 'query',
            'form_placeholder' => $tags ? d__('news', 'Enter a tag') : d__('news', 'Search query'),
        ]));
    }
}
