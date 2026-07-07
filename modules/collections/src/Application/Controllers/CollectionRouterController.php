<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Collections\Application\DTO\PublicItemDTO;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Application\UseCases\GetPublicItemUseCase;
use Johncms\Modules\Collections\Application\UseCases\ListPublicItemsUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\NavChain;
use Johncms\System\View\Render;

/**
 * Public URL resolver: the low-priority catch-all that maps root URLs to
 * collections (`/blog`, `/blog/tech`, `/blog/tech/hello.html`). It is also the
 * 404 path for every otherwise-unmatched URL.
 */
final readonly class CollectionRouterController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private CollectionCodeCacheInterface $codeCache,
        private ContentCollectionRepositoryInterface $collectionRepository,
        private ContentCollectionSectionRepositoryInterface $sectionRepository,
        private ListPublicItemsUseCase $listItems,
        private GetPublicItemUseCase $getItem,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('collections');
    }

    public function __invoke(string $route): string
    {
        $segments = array_values(array_filter(explode('/', $route), static fn (string $s): bool => $s !== ''));
        if ($segments === []) {
            $this->notFound();
        }

        $code = array_shift($segments);
        $map = $this->codeCache->map();
        if (! isset($map[$code])) {
            $this->notFound();
        }

        $collection = $this->collectionRepository->findById($map[$code]);
        if ($collection === null || ! $collection->active) {
            $this->notFound();
        }

        $last = end($segments);
        if (is_string($last) && str_ends_with($last, '.html')) {
            array_pop($segments);
            $sectionId = $this->resolveSection($collection->id, $segments);
            if ($sectionId === false) {
                $this->notFound();
            }

            return $this->renderDetail($collection, $sectionId, substr($last, 0, -5));
        }

        $sectionId = $this->resolveSection($collection->id, $segments);
        if ($sectionId === false) {
            $this->notFound();
        }

        return $this->renderListing($collection, $sectionId, '/' . trim($route, '/'));
    }

    /**
     * Active child sections of the current level as navigation links.
     *
     * @return list<array{name: string, url: string}>
     */
    private function childSectionRows(int $collectionId, ?int $sectionId, string $basePath): array
    {
        $rows = [];
        foreach ($this->sectionRepository->getActiveChildren($collectionId, $sectionId) as $section) {
            $rows[] = ['name' => $section->name, 'url' => $basePath . '/' . $section->code];
        }

        return $rows;
    }

    /**
     * Walks the section path by code. Returns the resolved section id, null for
     * the collection root, or false when a segment does not resolve.
     *
     * @param list<string> $segments
     */
    private function resolveSection(int $collectionId, array $segments): int|null|false
    {
        $parent = null;
        foreach ($segments as $segment) {
            $section = $this->sectionRepository->findByCode($collectionId, $parent, $segment);
            if ($section === null || ! $section->active) {
                return false;
            }
            $parent = $section->id;
        }

        return $parent;
    }

    private function renderListing(ContentCollection $collection, ?int $sectionId, string $basePath): string
    {
        $perPage = isset($collection->settings['per_page']) ? (int) $collection->settings['per_page'] : null;
        $pagination = $this->paginationFactory->create($this->listItems->count($collection->id, $sectionId), $perPage);

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $items = $this->listItems->getPage($collection->id, $sectionId, $pagination->getPerPage(), $pagination->getOffset());
        $title = $this->breadcrumbs($collection, $sectionId);

        $meta = new PageMeta($title . ' — ' . $collection->name, $pagination->getCurrentPage(), $collection->description ?? '');
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $title,
            'description' => $meta->description,
        ]);

        // The root listing shows items from every section, so each URL must use the
        // item's own section path rather than the current listing path.
        $sectionPaths = $this->resolveSectionPaths($items);

        return $this->render->render('collections::public/listing', [
            'sections'   => $this->childSectionRows($collection->id, $sectionId, $basePath),
            'items'      => array_map(fn (PublicItemDTO $item): array => [
                'name'    => $item->name,
                'preview' => $item->previewText,
                'url'     => '/' . $collection->code
                    . ($item->sectionId !== null ? $sectionPaths[$item->sectionId] : '')
                    . '/' . $item->code . '.html',
            ], $items),
            'pagination' => $pagination->render(),
        ]);
    }

    /**
     * Builds a map of section id => "/code/subcode" path for the sections
     * referenced by the given items, resolving each distinct section only once.
     *
     * @param list<PublicItemDTO> $items
     * @return array<int, string>
     */
    private function resolveSectionPaths(array $items): array
    {
        $paths = [];
        foreach ($items as $item) {
            if ($item->sectionId === null || isset($paths[$item->sectionId])) {
                continue;
            }

            $path = '';
            foreach ($this->sectionRepository->getPathTo($item->sectionId) as $section) {
                $path .= '/' . $section->code;
            }
            $paths[$item->sectionId] = $path;
        }

        return $paths;
    }

    private function renderDetail(ContentCollection $collection, ?int $sectionId, string $code): string
    {
        $detail = $this->getItem->execute($collection->id, $sectionId, $code);
        if ($detail === null) {
            $this->notFound();
        }

        $this->breadcrumbs($collection, $sectionId);
        $this->navChain->add($detail->name);

        $meta = new PageMeta($detail->name . ' — ' . $collection->name, 1, $detail->previewText ?? '');
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $detail->name,
            'description' => $meta->description,
        ]);

        return $this->render->render('collections::public/detail', ['item' => $detail]);
    }

    /**
     * Adds the collection and section path to the breadcrumb chain and returns the
     * current level title (section name, or collection name at the root).
     */
    private function breadcrumbs(ContentCollection $collection, ?int $sectionId): string
    {
        $url = '/' . $collection->code;
        $this->navChain->add($collection->name, $url);
        $title = $collection->name;

        if ($sectionId !== null) {
            foreach ($this->sectionRepository->getPathTo($sectionId) as $section) {
                $url .= '/' . $section->code;
                $this->navChain->add($section->name, $url);
                $title = $section->name;
            }
        }

        return $title;
    }

    private function notFound(): never
    {
        pageNotFound();
        exit;
    }
}
