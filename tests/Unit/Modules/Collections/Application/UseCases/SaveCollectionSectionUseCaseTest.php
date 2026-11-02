<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionFormException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\RunsMigrations;

final class SaveCollectionSectionUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private int $collection;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('collections');
        $this->collection = Capsule::table('collections')->insertGetId(['code' => 'blog', 'name' => 'Blog']);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    private function form(string $code = 'tech', ?int $parent = null, ?int $collection = null): CollectionSectionFormDTO
    {
        return new CollectionSectionFormDTO($collection ?? $this->collection, $parent, $code, 'Technology', 'Raw <b>description</b>', true, 100, ['seo' => ['title' => 'A & B']]);
    }

    public function testCreateUpdateAndMoveKeepDescriptionAndMetadata(): void
    {
        $writer = CollectionItemWriterFactory::sectionWriter();
        $root = $writer->execute(null, $this->form('root'));
        $id = $writer->execute(null, $this->form());
        self::assertSame($id, $writer->execute($id, $this->form(parent: $root)));
        $section = CollectionItemWriterFactory::sectionsApi()->findSection($this->collection, $id);
        self::assertSame($root, $section->parent);
        self::assertSame('Raw <b>description</b>', $section->description);
        self::assertSame(['seo' => ['title' => 'A & B']], $section->metadata);
        self::assertInstanceOf(\DateTimeImmutable::class, $section->createdAt);
    }

    public function testDuplicateSiblingCodeIsRejected(): void
    {
        $writer = CollectionItemWriterFactory::sectionWriter();
        $writer->execute(null, $this->form());
        $this->expectException(CollectionSectionCodeAlreadyExistsException::class);
        $writer->execute(null, $this->form());
    }

    public function testSameCodeUnderDifferentParentsIsAllowed(): void
    {
        $writer = CollectionItemWriterFactory::sectionWriter();
        $root = $writer->execute(null, $this->form());
        $child = $writer->execute(null, $this->form(parent: $root));
        self::assertNotSame($root, $child);
    }

    public function testMoveIntoDescendantIsRejectedAtomically(): void
    {
        $writer = CollectionItemWriterFactory::sectionWriter();
        $root = $writer->execute(null, $this->form('root'));
        $child = $writer->execute(null, $this->form(parent: $root));
        try {
            $writer->execute($root, $this->form('root', $child));
            self::fail('A cycle was accepted.');
        } catch (CollectionSectionFormException $exception) {
            self::assertArrayHasKey('parent', $exception->errors);
        }
        self::assertNull(CollectionItemWriterFactory::sectionsApi()->findSection($this->collection, $root)->parent);
    }

    public function testMoveIntoSelfIsRejected(): void
    {
        $writer = CollectionItemWriterFactory::sectionWriter();
        $id = $writer->execute(null, $this->form());
        $this->expectException(CollectionSectionFormException::class);
        $writer->execute($id, $this->form(parent: $id));
    }

    public function testForeignParentIsRejected(): void
    {
        $other = Capsule::table('collections')->insertGetId(['code' => 'other', 'name' => 'Other']);
        $writer = CollectionItemWriterFactory::sectionWriter();
        $parent = $writer->execute(null, $this->form(collection: $other));
        $this->expectException(CollectionSectionFormException::class);
        $writer->execute(null, $this->form(parent: $parent));
    }

    public function testUpdateCannotMoveASectionBetweenCollections(): void
    {
        $other = Capsule::table('collections')->insertGetId(['code' => 'other', 'name' => 'Other']);
        $writer = CollectionItemWriterFactory::sectionWriter();
        $id = $writer->execute(null, $this->form());
        $this->expectException(CollectionSectionFormException::class);
        $writer->execute($id, $this->form(collection: $other));
    }

    public function testUpdateOfMissingIdDoesNotCreateASection(): void
    {
        $this->expectException(CollectionSectionFormException::class);
        CollectionItemWriterFactory::sectionWriter()->execute(9999, $this->form());
    }

    public function testCreationParticipatesInOuterTransaction(): void
    {
        Capsule::connection()->beginTransaction();
        CollectionItemWriterFactory::sectionWriter()->execute(null, $this->form());
        Capsule::connection()->rollBack();
        self::assertSame(0, Capsule::table('collection_sections')->count());
    }

    #[DataProvider('invalidInput')]
    public function testInvalidApiInputIsRejected(string $code, string $name, int|string $sort, array $metadata): void
    {
        $this->expectException(CollectionSectionFormException::class);
        CollectionItemWriterFactory::sectionWriter()->execute(null, new CollectionSectionFormDTO(
            $this->collection, null, $code, $name, null, true, $sort, $metadata
        ));
    }

    public static function invalidInput(): iterable
    {
        yield 'empty name' => ['tech', ' ', 100, []];
        yield 'long name' => ['tech', str_repeat('x', 256), 100, []];
        yield 'reserved path' => ['..', 'Tech', 100, []];
        yield 'bad sort' => ['tech', 'Tech', 'abc', []];
        yield 'sort overflow' => ['tech', 'Tech', '2147483648', []];
        yield 'list metadata' => ['tech', 'Tech', 100, ['value']];
        yield 'object metadata' => ['tech', 'Tech', 100, ['value' => new \stdClass()]];
        yield 'large metadata' => ['tech', 'Tech', 100, ['value' => str_repeat('x', 65536)]];
    }
}
