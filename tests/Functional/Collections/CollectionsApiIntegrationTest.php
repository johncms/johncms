<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use DateTimeImmutable;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsImportApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemHistoryDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Domain\Enums\FilterOperator;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Query\FieldFilterDTO;
use Johncms\Updates\SystemOperationContext;
use LogicException;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;

final class CollectionsApiIntegrationTest extends FunctionalTestCase
{
    public function testReadingAndPatchingDoNotExposeOrmOrEraseUnspecifiedFields(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'api', 'name' => 'API']);
        Fixture::insert('collection_fields', ['collection_id' => $collection, 'code' => 'name', 'name' => 'Name', 'type' => 'string']);
        $api = $this->container()->get(CollectionsApiInterface::class);
        $id = $api->addItem(new CollectionItemFormDTO($collection, null, 'entry', 'Entry', true, null, null, 100, null, null, ['name' => 'Custom']));
        $api->patchItem($id, new CollectionItemPatchDTO(attributes: ['detailText' => 'Changed']));
        $page = $api->getPage(new ContentCollectionItemQuery(collectionId: $collection, fields: ['name'], filters: [new FieldFilterDTO('field.name', FilterOperator::Eq, 'Custom')]));
        self::assertSame(1, $page->total);
        self::assertSame('Entry', $page->items[0]->name);
        self::assertSame('Changed', $page->items[0]->detailText);
        self::assertSame('Custom', $page->items[0]->values['name']);
        self::assertInstanceOf(DateTimeImmutable::class, $page->items[0]->createdAt);
    }

    public function testImporterAndWriterShareTheTrustedContextFromTheContainer(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'history', 'name' => 'History']);
        $importer = $this->container()->get(CollectionsImportApiInterface::class);
        $context = $this->container()->get(SystemOperationContext::class);
        $id = $context->runTrusted(fn (): int => $importer->importItem(
            new CollectionItemFormDTO($collection, null, 'old', 'Old', true, null, null, 100, null, null, []),
            new CollectionItemHistoryDTO(new DateTimeImmutable('2001-01-01T12:30:00+03:00'), new DateTimeImmutable('2002-01-01T00:00:00Z'), 7, 8, 123)
        ));
        $item = $this->container()->get(CollectionsApiInterface::class)->getItem($id);
        self::assertSame('2001-01-01 09:30:00', $item->createdAt->format('Y-m-d H:i:s'));
        self::assertSame(123, $item->viewCount);
        self::assertSame(7, $item->createdBy);
        $this->expectException(LogicException::class);
        $context->requireActive();
    }
}
