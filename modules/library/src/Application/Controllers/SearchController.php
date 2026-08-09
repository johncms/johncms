<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Modules\Library\Application\Services\Utils;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Utils\PlainTextFormatter;
use Twig\Markup;

final readonly class SearchController
{
    public function __construct(
        private NavChain $navChain,
        private DateFormatterInterface $dateFormatter,
        private LibraryTextRepositoryInterface $repository,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Search'));

        $rawQuery = $request->query->has('search') ? $request->queryParam('search') : false;
        $query = $rawQuery !== false ? rawurldecode(trim($rawQuery)) : false;
        if ($query !== false) {
            $query = trim(preg_replace('/[+\-><()~*"]+/', ' ', $query));
        }

        $inTitle = $request->query->has('t');

        if ($query === false || $query === '') {
            $documentTitle = __('Search') . ' — ' . __('Library');

            return new ViewResponse('@library/public/search.twig', [
                'title'       => $documentTitle,
                'page_title'  => __('Search'),
                'description' => $documentTitle,
                'total'       => 0,
                'query'       => '',
                'in_titles'   => $inTitle,
                'results'     => [],
                'pagination'  => null,
            ]);
        }

        $items = [];
        $error = mb_strlen($query) < 4 || mb_strlen($query) > 64;

        $total = $error ? 0 : $this->repository->searchCount($query, $inTitle);

        $pagination = $this->paginationFactory->create($total);
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $pageTitle = __('Search results for: %s', $query);
        $documentTitle = $pageTitle . ' — ' . __('Library');
        $meta = new PageMeta($documentTitle, $pagination->getCurrentPage());

        if (! $error && $total) {
            $words = explode(' ', $query);
            $texts = $this->repository->search($query, $inTitle, $pagination->getCurrentPage(), $pagination->getPerPage());

            foreach ($texts as $text) {
                $plainText = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $text->text)));
                $pos = 100;
                foreach ($words as $word) {
                    if (($found = mb_stripos($plainText, str_replace('*', '', $word))) !== false) {
                        $pos = $found;
                        break;
                    }
                }
                $pos = $pos < 100 ? 100 : $pos;

                // The keywords are wrapped in a highlight, so both values become markup and are
                // escaped here rather than by the template.
                $name = PlainTextFormatter::escape($text->name);
                $excerpt = PlainTextFormatter::escape(mb_substr($plainText, $pos - 100, 400));

                foreach ($words as $word) {
                    if ($inTitle) {
                        $name = Utils::replaceKeywords($word, $name);
                    } else {
                        $excerpt = Utils::replaceKeywords($word, $excerpt);
                    }
                }

                $items[] = [
                    'id'          => $text->id,
                    'url'         => $text->url,
                    'name'        => new Markup($name, 'UTF-8'),
                    'text'        => new Markup($excerpt, 'UTF-8'),
                    'uploader_id' => $text->uploader_id,
                    'uploader'    => $text->uploader,
                    'date'        => $this->dateFormatter->format($text->time),
                    'count_views' => $text->count_views,
                ];
            }
        }

        return new ViewResponse('@library/public/search.twig', [
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
            'total'       => $total,
            'query'       => $query,
            'in_titles'   => $inTitle,
            'results'     => $items,
            'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
        ]);
    }
}
