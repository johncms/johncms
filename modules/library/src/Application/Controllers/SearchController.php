<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Modules\Library\Application\Services\Utils;

final readonly class SearchController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Tools $tools,
        private LibraryTextRepositoryInterface $repository,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(): string
    {
        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Search'));

        $rawQuery = $this->request->getQuery('search', false);
        $query = $rawQuery !== false ? rawurldecode(trim((string) $rawQuery)) : false;
        if ($query !== false) {
            $query = trim(preg_replace('/[+\-><()~*"]+/', ' ', $query));
        }

        $inTitle = $this->request->getQuery('t', false) !== false;

        if ($query === false || $query === '') {
            $documentTitle = __('Search') . ' — ' . __('Library');
            $this->render->addData([
                'title'       => $documentTitle,
                'page_title'  => __('Search'),
                'description' => $documentTitle,
            ]);
            return $this->render->render('library::search', [
                'total'      => 0,
                'search'     => false,
                'search_t'   => $inTitle,
                'pagination' => '',
                'items'      => [],
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

        $pageTitle = __('Search results for: %s', htmlspecialchars($query));
        $documentTitle = $pageTitle . ' — ' . __('Library');
        $meta = new PageMeta($documentTitle, $pagination->getCurrentPage());

        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        if (! $error) {
            if ($total) {
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

                    $name = $this->tools->checkout($text->name);
                    $excerpt = $this->tools->checkout(mb_substr($plainText, $pos - 100, 400));

                    foreach ($words as $word) {
                        if ($inTitle) {
                            $name = Utils::replaceKeywords($word, $name);
                        } else {
                            $excerpt = Utils::replaceKeywords($word, $excerpt);
                        }
                    }

                    $author = $text->uploader_id
                        ? '<a href="' . config('johncms')['homeurl'] . '/profile/' . $text->uploader_id . '">' . $this->tools->checkout($text->uploader) . '</a>'
                        : $this->tools->checkout($text->uploader);

                    $items[] = [
                        'id'          => $text->id,
                        'url'         => $text->url,
                        'name'        => $name,
                        'text'        => $excerpt,
                        'author'      => $author,
                        'time'        => $this->tools->displayDate($text->time),
                        'count_views' => $text->count_views,
                    ];
                }
            }
        }

        $search = htmlspecialchars($query);

        return $this->render->render('library::search', [
            'total'      => $total,
            'search'     => $search,
            'search_t'   => $inTitle,
            'pagination' => $pagination->render(),
            'items'      => $items,
        ]);
    }
}
