<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Api;

use DateTimeImmutable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Content\ContentRendererInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsImportApi;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemHistoryDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionFormException;
use Johncms\Modules\Collections\Application\Services\CollectionSearchIndexer;
use Johncms\Modules\Collections\Application\Services\ItemContentFormatter;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\CollectionSearchRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionSectionRepository;
use Johncms\Updates\SystemOperationContext;
use LogicException;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\RunsMigrations;

final class CollectionsImportApiTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private SystemOperationContext $context;
    private CollectionsImportApi $api;
    private int $collection;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('collections');
        $this->context = new SystemOperationContext();
        $this->api = new CollectionsImportApi(
            $this->context, CollectionItemWriterFactory::create(context: $this->context), CollectionItemWriterFactory::sectionWriter($this->context),
            new CollectionSearchIndexer(
                new CollectionSearchRepository(),
                new CollectionTransaction(),
                new ContentCollectionRepository(),
                CollectionItemWriterFactory::itemRepository(),
                new ItemContentFormatter($this->createStub(ContentRendererInterface::class))
            )
        );
        $this->collection = Capsule::table('collections')->insertGetId(['code' => 'history', 'name' => 'History']);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testSearchRebuildRequiresTrustedContext(): void
    {
        $this->context->runTrusted(fn () => $this->api->rebuildSearch($this->collection));
        $this->expectException(LogicException::class);
        $this->api->rebuildSearch($this->collection);
    }

    public function testImportRequiresTrustedScopeAndCannotBeEnabledThroughItsInput(): void
    {
        $this->expectException(LogicException::class);
        $this->api->importItem($this->form(), $this->history());
    }

    public function testImportedItemKeepsDatesAuthorsAndViews(): void
    {
        $id = $this->context->runTrusted(fn (): int => $this->api->importItem($this->form(), $this->history()));
        $item = CollectionItemWriterFactory::api()->getItem($id);
        self::assertSame('2001-02-03 04:05:06', $item->createdAt->format('Y-m-d H:i:s'));
        self::assertSame('2002-03-04 05:06:07', $item->updatedAt->format('Y-m-d H:i:s'));
        self::assertSame(7, $item->createdBy);
        self::assertSame(8, $item->updatedBy);
        self::assertSame(89, $item->viewCount);
        $this->expectException(LogicException::class);
        $this->api->importItem($this->form(), $this->history());
    }

    public function testImportedSectionKeepsHistoricalTimestamps(): void
    {
        $id = $this->context->runTrusted(fn (): int => $this->api->importSection(
            new CollectionSectionFormDTO($this->collection, null, 'Release.V10', 'Release', 'Original text', true, 100),
            $this->history()->createdAt, $this->history()->updatedAt
        ));
        $section = Capsule::table('collection_sections')->where('id', $id)->first();
        self::assertSame('2001-02-03 04:05:06', $section->created_at);
        self::assertSame('2002-03-04 05:06:07', $section->updated_at);
        self::assertSame('Original text', $section->description);
    }

    public function testAbsentHistoricalDatesRemainNullInsteadOfUsingTheImportTime(): void
    {
        $updated = new DateTimeImmutable('2002-03-04 05:06:07+00:00');
        $id = $this->context->runTrusted(fn (): int => $this->api->importItem($this->form(), new CollectionItemHistoryDTO(null, $updated)));
        $item = CollectionItemWriterFactory::api()->getItem($id);
        self::assertNull($item->createdAt);
        self::assertSame('2002-03-04 05:06:07', $item->updatedAt?->format('Y-m-d H:i:s'));
        $section = $this->context->runTrusted(fn (): int => $this->api->importSection(
            new CollectionSectionFormDTO($this->collection, null, 'undated', 'Undated', null, true, 100), null, null
        ));
        self::assertNull(Capsule::table('collection_sections')->where('id', $section)->value('created_at'));
        self::assertNull(Capsule::table('collection_sections')->where('id', $section)->value('updated_at'));
    }

    public function testImportUsesNormalRequiredFieldValidation(): void
    {
        Capsule::table('collection_fields')->insert(['collection_id' => $this->collection, 'code' => 'required', 'name' => 'Required', 'type' => 'integer', 'required' => true]);
        $this->expectException(CollectionItemFormException::class);
        $this->context->runTrusted(fn (): int => $this->api->importItem($this->form(), $this->history()));
    }

    public function testImportParticipatesInTheCallersTransaction(): void
    {
        Capsule::connection()->beginTransaction();
        $this->context->runTrusted(fn (): int => $this->api->importItem($this->form(), $this->history()));
        Capsule::connection()->rollBack();
        self::assertSame(0, Capsule::table('collection_items')->count());
    }

    public function testCallingTheWriterDirectlyDoesNotBypassTrust(): void
    {
        $this->expectException(LogicException::class);
        CollectionItemWriterFactory::create()->execute(id: null, dto: $this->form(), history: $this->history());
    }

    public function testSectionImportAlsoRequiresTheTrustedScope(): void
    {
        $this->expectException(LogicException::class);
        $this->api->importSection(new CollectionSectionFormDTO($this->collection, null, 'section', 'Section', null, true, 100),
            $this->history()->createdAt, $this->history()->updatedAt);
    }

    public function testSectionImportRejectsAnInvalidParent(): void
    {
        $this->expectException(CollectionSectionFormException::class);
        $this->context->runTrusted(fn (): int => $this->api->importSection(
            new CollectionSectionFormDTO($this->collection, 999, 'section', 'Section', null, true, 100),
            $this->history()->createdAt, $this->history()->updatedAt
        ));
    }

    private function form(): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO($this->collection, null, 'original', 'Original', true, null, null, 100, null, 'Original text', []);
    }

    private function history(): CollectionItemHistoryDTO
    {
        return new CollectionItemHistoryDTO(new DateTimeImmutable('2001-02-03 04:05:06+00:00'), new DateTimeImmutable('2002-03-04 05:06:07+00:00'), 7, 8, 89);
    }
}
