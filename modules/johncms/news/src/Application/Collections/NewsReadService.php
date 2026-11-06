<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use DateTimeImmutable;
use DateTimeZone;
use Johncms\Http\Session;
use Johncms\Modules\Collections\Application\Api\CollectionLookupApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsPublicApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsPreviewApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Query\CollectionTextSearchDTO;
use Johncms\Modules\Collections\Domain\Query\FieldFilterDTO;
use Johncms\Modules\Collections\Domain\Query\OrderByDTO;
use Johncms\Modules\Collections\Domain\Enums\FilterOperator;
use Johncms\Modules\Collections\Domain\Enums\SortDirection;
use Johncms\Modules\News\Application\DTO\NewsQueryDTO;
use Johncms\Modules\News\Application\DTO\NewsPageDTO;
use Johncms\Modules\News\Application\DTO\NewsArticleViewDTO;
use Johncms\Modules\News\Application\DTO\NewsSectionViewDTO;
use Johncms\Modules\News\Application\Exceptions\NewsCollectionUnavailableException;
use Johncms\Modules\News\Application\Exceptions\NewsItemNotFoundException;
use Johncms\Modules\News\Domain\Repository\NewsConfigRepositoryInterface;
use Johncms\Sitemap\SitemapUrlEntry;

final readonly class NewsReadService implements NewsReadServiceInterface
{
    public function __construct(
        private CollectionLookupApiInterface $lookup,
        private CollectionsPublicApiInterface $items,
        private CollectionsPreviewApiInterface $preview,
        private CollectionSectionsApiInterface $sections,
        private NewsViewMapperInterface $mapper,
        private NewsCollectionOwnerUrls $urls,
        private NewsConfigRepositoryInterface $settings,
        private Session $session,
        private \Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface $schema,
    ) {
    }

    public function collection(): CollectionRecordDTO
    {
        return $this->lookup->findDeclared(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY)
            ?? throw new NewsCollectionUnavailableException(d__('news', 'The news collection is missing. Run system:update.'));
    }

    public function page(NewsQueryDTO $query = new NewsQueryDTO()): NewsPageDTO
    {
        $collection = $this->collection();
        $page = $this->items->getPage($this->query($collection, $query));
        return new NewsPageDTO($this->mapper->articles($collection, $page->items), $page->total, $page->limit, $page->offset);
    }

    public function count(NewsQueryDTO $query = new NewsQueryDTO()): int
    {
        return $this->items->countItems($this->query($this->collection(), $query));
    }

    public function findById(int $itemId): ?NewsArticleViewDTO
    {
        $collection = $this->collection();
        $item = $this->items->findItem($collection->id, $itemId);
        return $item !== null ? $this->mapper->articles($collection, [$item])[0] : null;
    }

    public function findByCode(string $category, string $code): ?NewsArticleViewDTO
    {
        $collection = $this->collection();
        $section = $this->resolveSection($category);
        $item = $this->items->findItemByCode($collection->id, $section?->record->id, $code);
        return $item !== null ? $this->mapper->articles($collection, [$item])[0] : null;
    }

    public function preview(int $itemId): ?NewsArticleViewDTO
    {
        $collection = $this->collection();
        $item = $this->preview->findItem($collection->id, $itemId);
        return $item !== null ? $this->mapper->articles($collection, [$item])[0] : null;
    }

    public function sections(?int $parentId = null): array
    {
        $collection = $this->collection();
        $visible = $this->visibleSections($collection);
        if ($parentId !== null && ! isset($visible[$parentId])) {
            throw $this->notFound();
        }
        return $this->mapper->sections($collection, array_values(array_filter($visible, static fn (CollectionSectionDTO $section): bool => $section->parent === $parentId)));
    }

    public function section(int $sectionId): ?NewsSectionViewDTO
    {
        $collection = $this->collection();
        $section = $this->visibleSections($collection)[$sectionId] ?? null;
        return $section !== null ? $this->mapper->section($collection, $section) : null;
    }

    public function resolveSection(string $category): ?NewsSectionViewDTO
    {
        $category = trim($category, '/');
        if ($category === '') {
            return null;
        }
        $collection = $this->collection();
        try {
            $section = $this->sections->resolvePath($collection->id, explode('/', $category));
        } catch (\InvalidArgumentException $exception) {
            throw $this->notFound($exception);
        }
        return $section !== null ? ($this->section($section->id) ?? throw $this->notFound()) : throw $this->notFound();
    }

    public function breadcrumbs(int $sectionId): array
    {
        $collection = $this->collection();
        if (! isset($this->visibleSections($collection)[$sectionId])) {
            throw $this->notFound();
        }
        return $this->mapper->sections($collection, $this->sections->getPath($collection->id, $sectionId));
    }

    public function homepage(): array
    {
        $settings = $this->settings->getSettings();
        if (empty($settings['homepage_show'])) {
            return [];
        }
        $days = max(0, (int) ($settings['homepage_days'] ?? 0));
        $quantity = max(1, min(1000, (int) ($settings['homepage_quantity'] ?? 3)));
        return $this->page(new NewsQueryDTO(limit: $quantity, publishedSince: $days > 0 ? $this->since($days) : null))->articles;
    }

    public function countNew(int $days): int
    {
        return $days > 0 ? $this->count(new NewsQueryDTO(publishedSince: $this->since($days))) : 0;
    }

    public function sitemap(string $homeUrl): iterable
    {
        $collection = $this->collection();
        $homeUrl = rtrim($homeUrl, '/');
        $sections = array_values($this->visibleSections($collection));
        $urls = $this->urls->sectionUrls($collection, $sections);
        foreach ($sections as $section) {
            if (isset($urls[$section->id])) {
                yield new SitemapUrlEntry($homeUrl . $urls[$section->id], ($section->updatedAt ?? $section->createdAt)?->format(DATE_W3C));
            }
        }
        $after = 0;
        $at = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        do {
            $items = $this->items->getItems(new ContentCollectionItemQuery(
                collectionId: $collection->id,
                filters: [new FieldFilterDTO('item.id', FilterOperator::Gt, $after)],
                orderBy: [new OrderByDTO('item.id', SortDirection::Asc)],
                limit: 500,
                fields: [],
                at: $at,
                includeText: false
            ));
            $urls = $this->urls->itemUrls($collection, $items);
            foreach ($items as $item) {
                if (isset($urls[$item->id])) {
                    yield new SitemapUrlEntry($homeUrl . $urls[$item->id], ($item->updatedAt ?? $item->createdAt)?->format(DATE_W3C));
                }
                $after = $item->id;
            }
        } while ($items !== []);
    }

    public function registerView(int $itemId): void
    {
        $collection = $this->collection();
        if ($this->items->findItem($collection->id, $itemId) === null) {
            throw $this->notFound();
        }
        $viewed = $this->session->get('news_viewed_items', []);
        $viewed = is_array($viewed) ? $viewed : [];
        if (! in_array($itemId, $viewed, true)) {
            $this->items->incrementViews($itemId);
            $this->session->set('news_viewed_items', [...$viewed, $itemId]);
        }
    }

    public function rootMetadata(): array
    {
        return $this->mapper->rootMetadata();
    }

    private function query(CollectionRecordDTO $collection, NewsQueryDTO $query): ContentCollectionItemQuery
    {
        if ($query->sectionId !== null && ! isset($this->visibleSections($collection)[$query->sectionId])) {
            throw $this->notFound();
        }
        $filters = [];
        if ($query->tag !== null && trim($query->tag) !== '') {
            $code = null;
            foreach ($this->schema->getFields($collection->id) as $field) {
                if ($field->role === 'tags') {
                    $code = $field->code;
                    break;
                }
            }
            if ($code === null) {
                throw new NewsCollectionUnavailableException(d__('news', 'The news collection is missing. Run system:update.'));
            }
            $filters[] = new FieldFilterDTO('field.' . $code, FilterOperator::Eq, $query->tag);
        }
        if ($query->publishedSince !== null) {
            $filters[] = new FieldFilterDTO('item.published_at', FilterOperator::Gte, $query->publishedSince);
        }
        return new ContentCollectionItemQuery(
            collectionId: $collection->id,
            sectionId: $query->sectionId,
            includeSubsections: $query->sectionId !== null && $query->includeSubsections,
            filters: $filters,
            limit: $query->limit,
            offset: $query->offset,
            search: trim($query->search) !== '' ? new CollectionTextSearchDTO($query->search) : null
        );
    }

    /** @return array<int, CollectionSectionDTO> */
    private function visibleSections(CollectionRecordDTO $collection): array
    {
        if (! $collection->active) {
            return [];
        }
        $visible = [];
        foreach ($this->sections->getTree($collection->id) as $node) {
            $section = $node->section;
            if ($section->active && ($section->parent === null || isset($visible[$section->parent]))) {
                $visible[$section->id] = $section;
            }
        }
        return $visible;
    }

    private function since(int $days): DateTimeImmutable
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('-' . $days . ' days');
    }

    private function notFound(?\Throwable $previous = null): NewsItemNotFoundException
    {
        return new NewsItemNotFoundException(message: d__('news', 'The requested article or section is not available.'), previous: $previous);
    }
}
