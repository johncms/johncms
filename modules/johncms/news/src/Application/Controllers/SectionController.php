<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Johncms\Exceptions\PageNotFoundException;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Modules\News\Application\DTO\NewsQueryDTO;
use Johncms\Modules\News\Application\Exceptions\NewsItemNotFoundException;
use Johncms\NavChain;

final readonly class SectionController
{
    public function __construct(
        private NewsReadServiceInterface $news,
        private NavChain $navChain,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function index(string $category = ''): ViewResponse
    {
        $this->navChain->add(d__('news', 'News'), '/news/');
        try {
            $section = $this->news->resolveSection($category);
        } catch (NewsItemNotFoundException) {
            throw new PageNotFoundException();
        }
        if ($section !== null) {
            foreach ($this->news->breadcrumbs($section->record->id) as $crumb) {
                $this->navChain->add($crumb->record->name, $crumb->url);
            }
        }
        $id = $section?->record->id;
        $pagination = $this->paginationFactory->create($this->news->count(new NewsQueryDTO(sectionId: $id)));
        $redirect = $this->paginationGuard->redirectUrl($pagination);
        if ($redirect !== null) {
            redirect($redirect);
        }
        $page = $this->news->page(new NewsQueryDTO(sectionId: $id, limit: $pagination->getPerPage(), offset: $pagination->getOffset()));
        return new ViewResponse('@news/public/index.twig', ($section?->metadata() ?? $this->news->rootMetadata()) + [
            'sections' => array_map(static fn ($entry): array => $entry->toTemplate(), $this->news->sections($id)),
            'articles' => array_map(static fn ($entry): array => $entry->toTemplate(), $page->articles),
            'pagination' => $pagination->hasPages() ? $pagination->render() : null, 'current_section' => $section?->toTemplate(),
            'workspace_url' => '/admin/collections/' . $this->news->collection()->id . '/items',
        ]);
    }
}
