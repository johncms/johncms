<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionDeletionTest extends FunctionalTestCase
{
    private int $collection;
    private int $root;
    private int $child;
    private int $target;
    private int $field;
    private int $first;
    private int $second;
    private CollectionsApiInterface $api;
    private CollectionSectionsApiInterface $sections;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Fixture::insert('collections', ['code' => 'delete-test', 'name' => 'Deletion', 'active' => 1, 'public' => 1]);
        $this->root = $this->section('root');
        $this->child = $this->section('child', $this->root);
        $this->target = $this->section('target');
        $this->field = Fixture::insert('collection_fields', ['collection_id' => $this->collection, 'code' => 'text', 'name' => 'Text', 'type' => 'string']);
        $this->first = $this->item('first', $this->root);
        $this->second = $this->item('second', $this->child);
        Fixture::insert('collection_item_values', ['item_id' => $this->first, 'field_id' => $this->field, 'value_string' => 'Kept value']);
        $this->api = $this->container()->get(CollectionsApiInterface::class);
        $this->sections = $this->container()->get(CollectionSectionsApiInterface::class);
        $this->container()->get(CollectionCodeCacheInterface::class)->invalidate();
    }

    protected function tearDown(): void
    {
        $this->container()->get(CollectionCodeCacheInterface::class)->invalidate();
        parent::tearDown();
    }

    public function testConfirmationShowsSubtreeCountsAndExplicitChoicesInModalAndPage(): void
    {
        $user = FunctionalUserFactory::createSupervisor();
        $url = $this->sectionUrl();
        foreach ([[], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']] as $server) {
            $response = $this->handleRequest($url, server: $server, cookies: $this->actingAs($user));
            self::assertSame(200, $response->getStatusCode());
            $html = (string) $response->getContent();
            self::assertNotSame('', $this->revision($html));
            self::assertStringContainsString('name="mode"', $html);
            self::assertStringContainsString('value="delete"', $html);
            self::assertStringContainsString('value="move"', $html);
            self::assertStringContainsString('<option value="' . $this->target . '"', $html);
            self::assertStringNotContainsString('<option value="' . $this->root . '"', $html);
            self::assertStringNotContainsString('<option value="' . $this->child . '"', $html);
        }
        $preview = $this->sections->previewDeletion($this->collection, $this->root);
        self::assertSame(2, $preview->sections);
        self::assertSame(2, $preview->items);
        self::assertSame(0, $preview->fields);
    }

    public function testDeletingItemsDoesNotTurnThemIntoRootItems(): void
    {
        $kept = $this->item('outside', null);
        $preview = $this->sections->previewDeletion($this->collection, $this->root);
        $this->sections->deleteSection($this->collection, $this->root, SectionDeletionMode::DeleteItems, expectedRevision: $preview->revision);
        self::assertNull($this->api->getItem($this->first));
        self::assertNull($this->api->getItem($this->second));
        self::assertNotNull($this->api->getItem($kept));
        self::assertSame(0, Capsule::table('collection_item_values')->count());
        self::assertSame([$this->target], Capsule::table('collection_sections')->pluck('id')->all());
    }

    public function testMovingItemsFlattensSubtreeAndPreservesTheirValuesAndPublicationSettings(): void
    {
        Capsule::table('collection_items')->where('id', $this->second)->update(['active' => false, 'view_count' => 89, 'created_by' => 7]);
        $this->sections->deleteSection($this->collection, $this->root, SectionDeletionMode::MoveItems, $this->target);
        $first = $this->api->getItem($this->first);
        $second = $this->api->getItem($this->second);
        self::assertSame($this->target, $first->sectionId);
        self::assertSame($this->target, $second->sectionId);
        self::assertSame('first', $first->code);
        self::assertSame('Kept value', $first->values['text']);
        self::assertFalse($second->active);
        self::assertSame(89, $second->viewCount);
        self::assertSame(7, $second->createdBy);
        self::assertSame('2001-01-01 00:00:00', $first->createdAt->format('Y-m-d H:i:s'));
        self::assertNull($this->sections->findSection($this->collection, $this->child));
    }

    #[DataProvider('invalidDestinations')]
    public function testMoveRejectsInvalidDestinationsWithoutDeletingAnything(string $destination): void
    {
        $target = match ($destination) {
            'missing' => 999999,
            'root' => null,
            'self' => $this->root,
            'descendant' => $this->child,
            'foreign' => $this->foreignSection(),
            default => throw new \LogicException('Unknown destination test case.'),
        };
        try {
            $this->sections->deleteSection($this->collection, $this->root, SectionDeletionMode::MoveItems, $target);
            self::fail('An invalid destination was accepted.');
        } catch (CollectionDeletionException $exception) {
            self::assertArrayHasKey('target', $exception->errors);
        }
        self::assertSame($this->root, $this->api->getItem($this->first)->sectionId);
        self::assertSame($this->child, $this->api->getItem($this->second)->sectionId);
        self::assertNotNull($this->sections->findSection($this->collection, $this->root));
    }

    public static function invalidDestinations(): iterable
    {
        foreach (['missing', 'root', 'self', 'descendant', 'foreign'] as $case) {
            yield $case => [$case];
        }
    }

    #[DataProvider('collisionLocations')]
    public function testCodeCollisionRollsBackEarlierMovesAndPreservesTheSubtree(bool $inTarget): void
    {
        if ($inTarget) {
            $this->item('second', $this->target);
        } else {
            Capsule::table('collection_items')->where('id', $this->second)->update(['code' => 'first']);
        }
        try {
            $this->sections->deleteSection($this->collection, $this->root, SectionDeletionMode::MoveItems, $this->target);
            self::fail('A conflicting code was accepted.');
        } catch (CollectionDeletionException $exception) {
            self::assertArrayHasKey('target', $exception->errors);
        }
        self::assertSame($this->root, $this->api->getItem($this->first)->sectionId);
        self::assertSame($this->child, $this->api->getItem($this->second)->sectionId);
        self::assertSame('2001-01-01 00:00:00', $this->api->getItem($this->first)->updatedAt->format('Y-m-d H:i:s'));
        self::assertSame('Kept value', $this->api->getItem($this->first)->values['text']);
        self::assertNotNull($this->sections->findSection($this->collection, $this->root));
    }

    public static function collisionLocations(): iterable
    {
        yield 'destination already has the code' => [true];
        yield 'two source sections have the same code' => [false];
    }

    public function testStaleHttpConfirmationShowsNewCountsAndRequiresAnotherSubmit(): void
    {
        $user = FunctionalUserFactory::createSupervisor();
        $html = (string) $this->handleRequest($this->sectionUrl(), cookies: $this->actingAs($user))->getContent();
        $revision = $this->revision($html);
        $new = $this->item('new', $this->child);
        $response = $this->postForm($this->sectionUrl(), ['mode' => 'delete', 'revision' => $revision], $user);
        self::assertSame(200, $response->getStatusCode());
        self::assertNotNull($this->api->getItem($new));
        $fresh = $this->revision((string) $response->getContent());
        self::assertNotSame($revision, $fresh);
        self::assertSame(3, $this->sections->previewDeletion($this->collection, $this->root)->items);
        $response = $this->postForm($this->sectionUrl(), ['mode' => 'delete', 'revision' => $fresh], $user);
        self::assertSame(302, $response->getStatusCode());
        self::assertNull($this->api->getItem($new));
    }

    public function testForgedPostCannotOmitChoiceOrRevision(): void
    {
        $user = FunctionalUserFactory::createSupervisor();
        foreach ([[], ['mode' => 'delete'], ['mode' => 'unknown', 'revision' => 'bad']] as $body) {
            self::assertSame(200, $this->postForm($this->sectionUrl(), $body, $user)->getStatusCode());
            self::assertNotNull($this->api->getItem($this->first));
        }
        self::assertSame(200, $this->postForm('/admin/collections/' . $this->collection . '/delete', [], $user)->getStatusCode());
        self::assertNotNull($this->api->findCollection('delete-test'));
    }

    public function testWholeCollectionDeletionUsesTheSameItemCleanupAndKeepsOtherCollections(): void
    {
        $otherSection = $this->foreignSection();
        $otherCollection = Capsule::table('collection_sections')->where('id', $otherSection)->value('collection_id');
        $otherItem = Fixture::insert('collection_items', ['collection_id' => $otherCollection, 'section_id' => $otherSection, 'code' => 'other', 'name' => 'Other']);
        $this->item('outside', null);
        $preview = $this->api->previewCollectionDeletion($this->collection);
        self::assertSame(3, $preview->items);
        self::assertSame(3, $preview->sections);
        self::assertSame(1, $preview->fields);
        $cache = $this->container()->get(CollectionCodeCacheInterface::class);
        self::assertArrayHasKey('delete-test', $cache->map());
        $this->api->deleteCollection($this->collection, $preview->revision);
        self::assertNull($this->api->findCollection('delete-test'));
        self::assertNotNull($this->api->getItem($otherItem));
        self::assertSame(0, Capsule::table('collection_fields')->where('collection_id', $this->collection)->count());
        self::assertSame(0, Capsule::table('collection_item_values')->count());
        self::assertArrayNotHasKey('delete-test', $cache->map());
    }

    public function testCollectionConfirmationRejectsAChangedFieldSet(): void
    {
        $preview = $this->api->previewCollectionDeletion($this->collection);
        Fixture::insert('collection_fields', ['collection_id' => $this->collection, 'code' => 'new', 'name' => 'New', 'type' => 'string']);
        $this->expectException(CollectionDeletionException::class);
        $this->api->deleteCollection($this->collection, $preview->revision);
    }

    public function testDeleteAndMoveParticipateInTheCallersTransaction(): void
    {
        Capsule::connection()->beginTransaction();
        $this->sections->deleteSection($this->collection, $this->root, SectionDeletionMode::MoveItems, $this->target);
        Capsule::connection()->rollBack();
        self::assertSame($this->root, $this->api->getItem($this->first)->sectionId);
        Capsule::connection()->beginTransaction();
        $this->api->deleteCollection($this->collection);
        Capsule::connection()->rollBack();
        self::assertSame('Kept value', $this->api->getItem($this->first)->values['text']);
        self::assertNotNull($this->sections->findSection($this->collection, $this->root));
    }

    public function testBrokenCrossCollectionLinksCannotTriggerAnExternalCascade(): void
    {
        $foreign = $this->foreignSection();
        Capsule::table('collection_sections')->where('id', $foreign)->update(['parent' => $this->root]);
        try {
            $this->sections->deleteSection($this->collection, $this->root, SectionDeletionMode::DeleteItems);
            self::fail('Cross-collection children must prevent deletion.');
        } catch (CollectionDeletionException $exception) {
            self::assertArrayHasKey('tree', $exception->errors);
        }
        self::assertTrue(Capsule::table('collection_sections')->where('id', $foreign)->exists());
        self::assertNotNull($this->api->getItem($this->first));
    }

    private function sectionUrl(): string
    {
        return '/admin/collections/' . $this->collection . '/sections/' . $this->root . '/delete';
    }

    private function revision(string $html): string
    {
        self::assertSame(1, preg_match('/name="revision" value="([a-f0-9]{64})"/', $html, $matches));
        return $matches[1];
    }

    private function section(string $code, ?int $parent = null): int
    {
        return Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'parent' => $parent, 'code' => $code, 'name' => $code, 'active' => 1]);
    }

    private function foreignSection(): int
    {
        $other = Fixture::insert('collections', ['code' => 'other', 'name' => 'Other']);
        return Fixture::insert('collection_sections', ['collection_id' => $other, 'code' => 'foreign', 'name' => 'Foreign']);
    }

    private function item(string $code, ?int $section): int
    {
        return Fixture::insert('collection_items', ['collection_id' => $this->collection, 'section_id' => $section, 'code' => $code, 'name' => $code, 'active' => 1, 'created_at' => '2001-01-01 00:00:00', 'updated_at' => '2001-01-01 00:00:00']);
    }
}
