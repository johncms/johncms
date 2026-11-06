<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsImportApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Updates\SystemOperationContext;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;

final class CollectionSectionsApiTest extends FunctionalTestCase
{
    private int $collection;
    private CollectionSectionsApiInterface $sections;
    private CollectionsApiInterface $items;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Fixture::insert('collections', ['code' => 'blog', 'name' => 'Blog']);
        $this->sections = $this->container()->get(CollectionSectionsApiInterface::class);
        $this->items = $this->container()->get(CollectionsApiInterface::class);
    }

    public function testTreePathAndFullCodeChainResolveWithinTheCollection(): void
    {
        $root = $this->section('root');
        $child = $this->section('child', $root);
        $leaf = $this->section('leaf', $child);
        $otherRoot = $this->section('other');
        $otherChild = $this->section('child', $otherRoot);
        self::assertSame([$root, $child, $leaf], array_map(static fn ($s) => $s->id, $this->sections->getPath($this->collection, $leaf)));
        self::assertSame($leaf, $this->sections->resolvePath($this->collection, ['root', 'child', 'leaf'])->id);
        self::assertSame($otherChild, $this->sections->resolvePath($this->collection, ['other', 'child'])->id);
        self::assertNull($this->sections->resolvePath($this->collection, ['child']));
        self::assertNull($this->sections->resolvePath($this->collection, []));
        self::assertNull($this->sections->findSection($this->collection + 100, $root));
        self::assertSame([], $this->sections->getPath($this->collection + 100, $root));
        self::assertSame([], $this->sections->getPath($this->collection, 99999));
        self::assertInstanceOf(CollectionSectionDTO::class, $this->sections->getTree($this->collection)[0]->section);
    }

    public function testCollectionRootExactAndSubtreeHaveMatchingCountsAndLists(): void
    {
        $root = $this->section('root');
        $child = $this->section('child', $root);
        $leaf = $this->section('leaf', $child);
        $sibling = $this->section('sibling');
        foreach ([null, $root, $child, $leaf, $sibling] as $section) {
            Fixture::insert('collection_items', ['collection_id' => $this->collection, 'section_id' => $section, 'code' => 'entry', 'name' => 'Entry', 'active' => 1]);
        }
        foreach ([
            [new ContentCollectionItemQuery($this->collection), 5],
            [new ContentCollectionItemQuery(collectionId: $this->collection, withoutSection: true), 1],
            [new ContentCollectionItemQuery(collectionId: $this->collection, sectionId: $root), 1],
            [new ContentCollectionItemQuery(collectionId: $this->collection, sectionId: $root, includeSubsections: true), 3],
            [new ContentCollectionItemQuery(collectionId: $this->collection, sectionId: $child, includeSubsections: true), 2],
            [new ContentCollectionItemQuery(collectionId: $this->collection, sectionId: 99999, includeSubsections: true), 0],
        ] as [$query, $expected]) {
            self::assertSame($expected, $this->items->countItems($query));
            self::assertCount($expected, $this->items->getItems($query));
        }
        $tree = $this->sections->getTree($this->collection);
        $node = array_values(array_filter($tree, static fn ($node) => $node->section->id === $root))[0];
        self::assertSame(1, $node->items);
        self::assertSame(3, $node->itemsInSubtree);
    }

    public function testSubtreeCannotExpandThroughAnotherCollectionsSection(): void
    {
        $other = Fixture::insert('collections', ['code' => 'other', 'name' => 'Other']);
        $foreign = $this->sections->addSection(new CollectionSectionFormDTO($other, null, 'foreign', 'Foreign', null, true, 100));
        self::assertSame(0, $this->items->countItems(new ContentCollectionItemQuery(collectionId: $this->collection, sectionId: $foreign, includeSubsections: true)));
    }

    public function testMovingSubtreeChangesPathAndCountsTogether(): void
    {
        $first = $this->section('first');
        $second = $this->section('second');
        $child = $this->section('child', $first);
        Fixture::insert('collection_items', ['collection_id' => $this->collection, 'section_id' => $child, 'code' => 'entry', 'name' => 'Entry', 'active' => 1]);
        $this->sections->updateSection($child, $this->form('child', $second));
        self::assertNull($this->sections->resolvePath($this->collection, ['first', 'child']));
        self::assertSame($child, $this->sections->resolvePath($this->collection, ['second', 'child'])->id);
        self::assertSame(0, $this->items->countItems(new ContentCollectionItemQuery(collectionId: $this->collection, sectionId: $first, includeSubsections: true)));
        self::assertSame(1, $this->items->countItems(new ContentCollectionItemQuery(collectionId: $this->collection, sectionId: $second, includeSubsections: true)));
    }

    public function testImportUsesSameWriterAndPreservesMetadataAndDates(): void
    {
        $id = $this->container()->get(SystemOperationContext::class)->runTrusted(fn () => $this->container()->get(CollectionsImportApiInterface::class)->importSection(
            new CollectionSectionFormDTO($this->collection, null, 'archive', 'Archive', 'Original', true, 100, ['title' => 'Archive']),
            new \DateTimeImmutable('2001-01-01T12:00:00+03:00'), new \DateTimeImmutable('2002-01-01T00:00:00Z')
        ));
        $section = $this->sections->findSection($this->collection, $id);
        self::assertSame('2001-01-01 09:00:00', $section->createdAt->format('Y-m-d H:i:s'));
        self::assertSame(['title' => 'Archive'], $section->metadata);
    }

    public function testDeletionIsCollectionScopedAndCascadesSubtreeOnly(): void
    {
        $root = $this->section('root');
        $child = $this->section('child', $root);
        $other = $this->section('other');
        try {
            $this->sections->deleteSection($this->collection + 100, $root, \Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode::DeleteItems);
            self::fail('A foreign delete was accepted.');
        } catch (CollectionDeletionException) {
            self::assertNotNull($this->sections->findSection($this->collection, $root));
        }
        $this->sections->deleteSection($this->collection, $root, \Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode::DeleteItems);
        self::assertNull($this->sections->findSection($this->collection, $child));
        self::assertNotNull($this->sections->findSection($this->collection, $other));
    }

    private function section(string $code, ?int $parent = null): int
    {
        return $this->sections->addSection($this->form($code, $parent));
    }

    private function form(string $code, ?int $parent = null): CollectionSectionFormDTO
    {
        return new CollectionSectionFormDTO($this->collection, $parent, $code, $code, null, true, 100);
    }
}
