<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionBulkApi;
use Johncms\Modules\Collections\Application\Api\CollectionBulkApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsUserApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Extension\CollectionChangeHandlerInterface;
use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;
use Johncms\Modules\Collections\Application\Services\CollectionRelationDeletion;
use Johncms\Modules\Collections\Application\Services\CollectionsPermissions;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionItemWriteAccessUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemValueRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use RuntimeException;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionBulkActionsTest extends FunctionalTestCase
{
    private int $collection;
    private int $first;
    private int $second;
    private CollectionsApiInterface $items;
    private CollectionBulkApiInterface $bulk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Fixture::insert('collections', ['code' => 'bulk', 'name' => 'Bulk', 'settings' => '{"has_sections":true}']);
        $this->first = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'code' => 'first', 'name' => 'First <record>', 'active' => 0, 'active_to' => '2020-01-01 12:00:00']);
        $this->second = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'code' => 'second', 'name' => 'Second record', 'active' => 0]);
        $this->handleRequest('/admin/collections/' . $this->collection . '/items', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        $this->items = $this->container()->get(CollectionsApiInterface::class);
        $this->bulk = $this->container()->get(CollectionBulkApiInterface::class);
    }

    public function testTheConfirmationNamesWhatKeepsARecordAndDeletesTheRest(): void
    {
        $this->container()->get(CollectionSchemaApiInterface::class)->saveField(null, new CollectionFieldFormDTO(
            collectionId: $this->collection, code: 'featured', name: 'Featured', type: FieldType::Relation, required: false, multiple: false, sort: 100,
            settings: ['target_collection_id' => $this->collection, 'default' => (string) $this->first],
        ));
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $this->collection . '/items/bulk';
        $form = ['bulk_started' => '1', 'bulk_complete' => '1', 'ids' => [(string) $this->first, (string) $this->second], 'bulk_action' => 'delete'];
        $response = $this->handleRequest($base, 'POST', $this->withCsrfToken($form), cookies: $this->actingAs($user), server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        self::assertSame(200, $response->getStatusCode());
        $html = (string) $response->getContent();
        self::assertStringNotContainsString('<html', $html, 'The modal gets the dialog alone.');
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        self::assertStringContainsString('Featured', $document->querySelector('.alert-danger')->textContent);
        $exclude = $document->querySelector('button[name="exclude"][value="blocked"]');
        self::assertNotNull($exclude);
        self::assertNull($document->querySelector('button[type="submit"]:not([name])'), 'A blocked deletion cannot be confirmed.');

        $response = $this->postForm($base, $form + ['exclude' => 'blocked', 'confirmed' => '1', 'revision' => 'stale'], $user);
        self::assertSame(200, $response->getStatusCode(), 'Leaving out the kept record asks again rather than deleting.');
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        $ids = array_map(static fn ($input): string => $input->getAttribute('value'), iterator_to_array($document->querySelectorAll('input[name="ids[]"]')));
        self::assertSame([(string) $this->second], $ids);
        $revision = $document->querySelector('input[name="revision"]')->getAttribute('value');
        self::assertSame(302, $this->postForm($base, ['bulk_started' => '1', 'bulk_complete' => '1', 'ids' => $ids, 'bulk_action' => 'delete', 'confirmed' => '1', 'revision' => $revision], $user)->getStatusCode());
        self::assertNotNull($this->items->getItem($this->first));
        self::assertNull($this->items->getItem($this->second));
    }

    public function testTheDialogOfASingleDeletionNamesWhatKeepsTheRecord(): void
    {
        $this->container()->get(CollectionSchemaApiInterface::class)->saveField(null, new CollectionFieldFormDTO(
            collectionId: $this->collection, code: 'featured', name: 'Featured', type: FieldType::Relation, required: false, multiple: false, sort: 100,
            settings: ['target_collection_id' => $this->collection, 'default' => (string) $this->first],
        ));
        $response = $this->handleRequest('/admin/collections/' . $this->collection . '/items/' . $this->first . '/delete', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()), server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        self::assertSame(200, $response->getStatusCode());
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        $reasons = $document->querySelector('.alert-danger')->textContent;
        self::assertStringContainsString('First <record>', $reasons);
        self::assertStringContainsString('Featured', $reasons);
        self::assertNull($document->querySelector('button[type="submit"]'), 'A blocked deletion cannot be confirmed.');
    }

    public function testTheMoveConfirmationShowsThePathOfTheDestination(): void
    {
        $parent = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'code' => 'tech', 'name' => 'Tech']);
        $child = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'parent' => $parent, 'code' => 'php', 'name' => 'PHP']);
        self::assertSame('Tech › PHP', $this->bulk->preview($this->collection, [$this->first], 'move', $child)->targetSectionName);
    }

    public function testAnErrorOfTheModalIsADialog(): void
    {
        $response = $this->handleRequest('/admin/collections/' . $this->collection . '/items/bulk', 'POST', $this->withCsrfToken(['bulk_started' => '1', 'bulk_complete' => '1', 'ids' => [], 'bulk_action' => 'publish']), cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()), server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $html = (string) $response->getContent();
        self::assertStringNotContainsString('<html', $html);
        self::assertStringContainsString('modal-content', $html);
        self::assertStringContainsString('alert-danger', $html);
    }

    public function testAPartialWriteAnswersOnlyForWhatItChanges(): void
    {
        $this->container()->get(CollectionSchemaApiInterface::class)->saveField(null, new CollectionFieldFormDTO($this->collection, 'extra', 'Extra', FieldType::String_, true, false, 100));
        Capsule::table('collection_items')->where('id', $this->first)->update(['active_from' => '2001-01-01 00:00:00', 'active_to' => '2000-01-01 00:00:00']);
        $this->items->patchItem($this->first, new CollectionItemPatchDTO(['active' => true]));
        self::assertTrue($this->items->getItem($this->first)->active);
        $preview = $this->bulk->preview($this->collection, [$this->first], 'hide');
        self::assertSame(1, $this->bulk->execute($this->collection, [$this->first], 'hide', $preview->revision));
        self::assertFalse($this->items->getItem($this->first)->active);
        foreach ([
            new CollectionItemPatchDTO(['activeTo' => '1999-01-01 00:00:00']),
            new CollectionItemPatchDTO(values: ['extra' => '']),
        ] as $patch) {
            try {
                $this->items->patchItem($this->first, $patch);
                self::fail('What a patch changes is still validated.');
            } catch (CollectionItemFormException $exception) {
                self::assertNotSame([], $exception->errors);
            }
        }
    }

    public function testNativeConfirmationPublishesSelectionAndPreservesTheFilteredReturn(): void
    {
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $this->collection . '/items';
        $back = $base . '?status=hidden&q=record&page=2';
        $form = ['bulk_started' => '1', 'bulk_complete' => '1', 'ids' => [(string) $this->first, (string) $this->second], 'bulk_action' => 'publish', 'back' => $back];
        $response = $this->postForm($base . '/bulk', $form, $user);
        self::assertSame(200, $response->getStatusCode());
        $html = (string) $response->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        self::assertStringContainsString('First &lt;record&gt;', $html);
        self::assertNotNull($document->querySelector('button[type="submit"]'));
        self::assertSame(0, Capsule::table('collection_items')->where('id', $this->first)->value('active'));
        $form['confirmed'] = '1';
        $form['revision'] = $document->querySelector('input[name="revision"]')->getAttribute('value');
        $response = $this->postForm($base . '/bulk', $form, $user);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame($back, $response->headers->get('Location'));
        foreach ([$this->first, $this->second] as $id) {
            self::assertTrue($this->items->getItem($id)->active);
            self::assertNull($this->items->getItem($id)->activeFrom);
            self::assertNull($this->items->getItem($id)->activeTo);
        }
    }

    public function testMoveAndHideUseCommonPatchesAndRejectForeignSections(): void
    {
        $target = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'code' => 'target', 'name' => 'Target']);
        $preview = $this->bulk->preview($this->collection, [$this->first, $this->second], 'move', $target);
        self::assertSame(2, $this->bulk->execute($this->collection, $preview->ids, 'move', $preview->revision, $target));
        foreach ($preview->ids as $id) {
            self::assertSame($target, $this->items->getItem($id)->sectionId);
        }
        $preview = $this->bulk->preview($this->collection, $preview->ids, 'hide');
        $this->bulk->execute($this->collection, $preview->ids, 'hide', $preview->revision);
        self::assertSame('2020-01-01 12:00:00', Capsule::table('collection_items')->where('id', $this->first)->value('active_to'));
        $foreign = Fixture::insert('collections', ['code' => 'foreign', 'name' => 'Foreign']);
        $section = Fixture::insert('collection_sections', ['collection_id' => $foreign, 'code' => 'foreign', 'name' => 'Foreign']);
        $this->expectException(CollectionItemFormException::class);
        $this->bulk->preview($this->collection, $preview->ids, 'move', $section);
    }

    public function testMoveCodeConflictsRollBackEarlierMovesAndReturnAnActionableError(): void
    {
        $target = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'code' => 'target', 'name' => 'Target']);
        Fixture::insert('collection_items', ['collection_id' => $this->collection, 'section_id' => $target, 'code' => 'second', 'name' => 'Conflicting record']);
        $preview = $this->bulk->preview($this->collection, [$this->first, $this->second], 'move', $target);
        $response = $this->postForm('/admin/collections/' . $this->collection . '/items/bulk', [
            'bulk_started' => '1', 'bulk_complete' => '1', 'confirmed' => '1', 'revision' => $preview->revision,
            'ids' => array_map(strval(...), $preview->ids), 'bulk_action' => 'move', 'target_section' => (string) $target,
        ], FunctionalUserFactory::createSupervisor());
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString(d__('collections', 'The destination contains conflicting record codes. Nothing was changed. Resolve the duplicates before moving.'), (string) $response->getContent());
        self::assertNull($this->items->getItem($this->first)->sectionId);
        self::assertNull($this->items->getItem($this->second)->sectionId);
    }

    public function testStaleSelectionOrChangedActionCannotBeConfirmedAndViewCountersDoNotInvalidateIt(): void
    {
        $ids = [$this->first, $this->second];
        $preview = $this->bulk->preview($this->collection, $ids, 'publish');
        $this->items->patchItem($this->second, new CollectionItemPatchDTO(['name' => 'Changed']));
        try {
            $this->bulk->execute($this->collection, $ids, 'publish', $preview->revision);
            self::fail('Changed content needs a fresh confirmation.');
        } catch (CollectionDeletionException) {
            self::assertFalse($this->items->getItem($this->first)->active);
        }
        $preview = $this->bulk->preview($this->collection, $ids, 'publish');
        try {
            $this->bulk->execute($this->collection, $ids, 'delete', $preview->revision);
            self::fail('The confirmation must cover the actual action.');
        } catch (CollectionDeletionException) {
            self::assertNotNull($this->items->getItem($this->first));
        }
        Capsule::table('collection_items')->where('id', $this->first)->update(['view_count' => 123]);
        self::assertSame(2, $this->bulk->execute($this->collection, $ids, 'publish', $preview->revision));
        self::assertSame(123, $this->items->getItem($this->first)->viewCount);
    }

    public function testDeletionIgnoresInternalCyclesAndClearsOnlyExternalOptionalRelations(): void
    {
        $schema = $this->container()->get(CollectionSchemaApiInterface::class);
        $schema->saveField(null, new CollectionFieldFormDTO(collectionId: $this->collection, code: 'cycle', name: 'Cycle', type: FieldType::Relation, required: false, multiple: false, sort: 100, settings: ['target_collection_id' => $this->collection]));
        $this->items->patchItem($this->first, new CollectionItemPatchDTO(values: ['cycle' => (string) $this->second]));
        $this->items->patchItem($this->second, new CollectionItemPatchDTO(values: ['cycle' => (string) $this->first]));
        $outside = Fixture::insert('collections', ['code' => 'outside', 'name' => 'Outside']);
        $schema->saveField(null, new CollectionFieldFormDTO(collectionId: $outside, code: 'links', name: 'Links', type: FieldType::Relation, required: false, multiple: true, sort: 100, settings: ['target_collection_id' => $this->collection, 'on_delete' => 'clear']));
        $source = $this->items->addItem(new CollectionItemFormDTO($outside, null, 'source', 'Source', false, null, null, 100, null, null, ['links' => [(string) $this->first, (string) $this->second]]));
        $preview = $this->bulk->preview($this->collection, [$this->first, $this->second], 'delete');
        self::assertTrue($preview->canExecute());
        self::assertSame(2, $preview->relations->clearValues);
        self::assertSame(0, $preview->relations->blockedValues);
        self::assertSame(2, $this->bulk->execute($this->collection, $preview->ids, 'delete', $preview->revision));
        self::assertNull($this->items->getItem($this->first));
        self::assertNull($this->items->getItem($this->second));
        self::assertArrayNotHasKey('links', $this->items->getItem($source)->values);
    }

    public function testRestrictionAndForeignItemsRejectTheWholeBatch(): void
    {
        $outside = Fixture::insert('collections', ['code' => 'outside', 'name' => 'Outside']);
        $this->container()->get(CollectionSchemaApiInterface::class)->saveField(null, new CollectionFieldFormDTO(collectionId: $outside, code: 'link', name: 'Link', type: FieldType::Relation, required: false, multiple: false, sort: 100, settings: ['target_collection_id' => $this->collection]));
        $source = $this->items->addItem(new CollectionItemFormDTO($outside, null, 'source', 'Source', false, null, null, 100, null, null, ['link' => (string) $this->second]));
        $preview = $this->bulk->preview($this->collection, [$this->first, $this->second], 'delete');
        self::assertFalse($preview->canExecute());
        try {
            $this->bulk->execute($this->collection, $preview->ids, 'delete', $preview->revision);
            self::fail('A restricted target prevents deletion of the entire selection.');
        } catch (CollectionDeletionException) {
            self::assertNotNull($this->items->getItem($this->first));
            self::assertNotNull($this->items->getItem($this->second));
        }
        $this->expectException(CollectionItemFormException::class);
        $this->bulk->preview($this->collection, [$this->first, $source], 'publish');
    }

    public function testMandatoryHandlerFailureRollsBackEarlierDeletionsAndHandlerWrites(): void
    {
        $handler = new class ($this->second) implements CollectionChangeHandlerInterface {
            public function __construct(private int $failOn)
            {
            }
            public function withinTransaction(CollectionChangeDTO $change): void
            {
                if ($change->id === $this->failOn) {
                    throw new RuntimeException('Mandatory bulk handler failed.');
                }
                Capsule::table('collection_items')->where('id', $this->failOn)->update(['view_count' => 99]);
            }
            public function afterCommit(CollectionChangeDTO $change): void
            {
            }
        };
        $container = $this->container();
        $transaction = new CollectionTransaction();
        $collections = $container->get(ContentCollectionRepositoryInterface::class);
        $items = $container->get(ContentCollectionItemRepositoryInterface::class);
        $relations = $container->get(CollectionRelationDeletion::class);
        $delete = new DeleteCollectionItemUseCase($items, $container->get(ContentCollectionItemValueRepositoryInterface::class), $collections, $transaction, $relations, new CollectionChangeDispatcher(handlers: [$handler], transaction: $transaction));
        $bulk = new CollectionBulkApi($transaction, $collections, $items, $container->get(ContentCollectionSectionRepositoryInterface::class), $container->get(ContentCollectionFieldRepositoryInterface::class), $container->get(EnsureCollectionContentAccessUseCase::class), $container->get(EnsureCollectionItemWriteAccessUseCase::class), $container->get(CollectionsUserApiInterface::class), $delete, $relations);
        $preview = $bulk->preview($this->collection, [$this->first, $this->second], 'delete');
        try {
            $bulk->execute($this->collection, $preview->ids, 'delete', $preview->revision);
            self::fail('Mandatory handler errors must abort the batch.');
        } catch (RuntimeException $exception) {
            self::assertSame('Mandatory bulk handler failed.', $exception->getMessage());
            self::assertNotNull($this->items->getItem($this->first));
            self::assertNotNull($this->items->getItem($this->second));
            self::assertSame(0, $this->items->getItem($this->second)->viewCount);
        }
    }

    public function testBulkNewsDeletionRunsTheOwnerCleanupForEveryRecord(): void
    {
        $this->container()->get(CollectionDeclarationsSynchronizer::class)->synchronize();
        $news = $this->items->findDeclaredCollection('johncms/news', 'articles');
        $ids = [];
        foreach (['first', 'second'] as $code) {
            $id = $this->items->addItem(new CollectionItemFormDTO($news->id, null, $code, $code, false, null, null, 100, null, null, []));
            $ids[] = $id;
            Fixture::insert('news_comments', ['article_id' => null, 'item_id' => $id, 'text' => 'Comment', 'user_data' => '{}', 'created_at' => '2026-01-01 12:00:00']);
            Fixture::insert('news_votes', ['article_id' => null, 'item_id' => $id, 'user_id' => 100, 'vote' => 1]);
        }
        $preview = $this->bulk->preview($news->id, $ids, 'delete');
        self::assertSame(2, $this->bulk->execute($news->id, $ids, 'delete', $preview->revision));
        self::assertSame(0, Capsule::table('news_comments')->whereIn('item_id', $ids)->count());
        self::assertSame(0, Capsule::table('news_votes')->whereIn('item_id', $ids)->count());
    }

    public function testSelectionLimitsAndMissingConfirmationFailBeforeAnyWrites(): void
    {
        foreach ([[], [$this->first, $this->first], array_fill(0, 101, $this->first), [(string) $this->first]] as $ids) {
            try {
                $this->bulk->preview($this->collection, $ids, 'publish');
                self::fail('Selections must contain distinct integer IDs within the limit.');
            } catch (CollectionItemFormException) {
                self::assertFalse($this->items->getItem($this->first)->active);
            }
        }
        $this->expectException(CollectionDeletionException::class);
        $this->bulk->execute($this->collection, [$this->first, $this->second], 'publish', '');
    }

    public function testForgedActionsAndTruncatedFormsCannotBypassPermissionsOrConfirmation(): void
    {
        Capsule::table('collections')->where('id', $this->collection)->update(['settings' => json_encode(['_content_permission' => 'news.manage', 'content_actions' => ['edit' => CollectionsPermissions::EDIT, 'publish' => CollectionsPermissions::PUBLISH, 'delete' => CollectionsPermissions::DELETE]])]);
        $user = FunctionalUserFactory::createWithPermissions(['news.manage', CollectionsPermissions::EDIT]);
        $base = '/admin/collections/' . $this->collection . '/items/bulk';
        $form = ['bulk_started' => '1', 'bulk_complete' => '1', 'ids' => [(string) $this->first, (string) $this->second], 'bulk_action' => 'publish'];
        foreach (['publish', 'hide', 'delete'] as $action) {
            $form['bulk_action'] = $action;
            self::assertSame(403, $this->postForm($base, $form, $user)->getStatusCode());
        }
        unset($form['bulk_complete']);
        self::assertSame(200, $this->postForm($base, $form, FunctionalUserFactory::createSupervisor())->getStatusCode());
        self::assertNotNull($this->items->getItem($this->first));
        self::assertFalse($this->items->getItem($this->second)->active);
    }
}
