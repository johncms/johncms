<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\PdoConnection;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionItemUseCase;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemValueRepositoryInterface;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemValueRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\RunsMigrations;

final class SaveCollectionItemUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private SaveCollectionItemUseCase $useCase;
    private ContentCollectionItemRepository $itemRepository;
    private int $collectionId;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('collections');

        $fieldRepository = new ContentCollectionFieldRepository();
        $this->itemRepository = new ContentCollectionItemRepository($fieldRepository, new ContentCollectionItemQueryCompiler());
        $this->useCase = CollectionItemWriterFactory::create();

        $now = date('Y-m-d H:i:s');
        $this->collectionId = Capsule::table('collections')->insertGetId([
            'code' => 'blog', 'name' => 'Blog', 'sort' => 100, 'active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $fieldRepository->create(['collection_id' => $this->collectionId, 'code' => 'author', 'name' => 'Author', 'type' => 'string', 'required' => false, 'multiple' => false, 'sort' => 100]);
        $fieldRepository->create(['collection_id' => $this->collectionId, 'code' => 'rating', 'name' => 'Rating', 'type' => 'integer', 'required' => false, 'multiple' => false, 'sort' => 200]);
        $fieldRepository->create(['collection_id' => $this->collectionId, 'code' => 'tag', 'name' => 'Tag', 'type' => 'string', 'required' => false, 'multiple' => true, 'sort' => 300]);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    /**
     * @param array<string, string|list<string>> $values
     */
    private function dto(string $code, array $values): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO(
            collectionId: $this->collectionId,
            sectionId: null,
            code: $code,
            name: 'Hello',
            active: true,
            activeFrom: null,
            activeTo: null,
            sort: 100,
            previewText: null,
            detailText: null,
            values: $values,
        );
    }

    public function testCreateWritesValuesIntoTypedColumns(): void
    {
        $newId = $this->useCase->execute(null, $this->dto('hello', [
            'author' => 'Alice',
            'rating' => '5',
            'tag'    => ['php', 'sql'],
        ]));

        $item = $this->itemRepository->findByCode($this->collectionId, null, 'hello');
        self::assertNotNull($item);
        self::assertSame($item->id, $newId);

        $map = $this->itemRepository->findWithValues($item->id)->getValuesMap();
        self::assertSame('Alice', $map['author']);
        self::assertSame(5, $map['rating']);
        self::assertSame(['php', 'sql'], $map['tag']);

        $ratingRow = Capsule::table('collection_item_values')
            ->join('collection_fields', 'collection_fields.id', '=', 'collection_item_values.field_id')
            ->where('collection_item_values.item_id', $item->id)
            ->where('collection_fields.code', 'rating')
            ->first();
        self::assertSame(5, (int) $ratingRow->value_int);
        self::assertNull($ratingRow->value_string);
    }

    public function testUpdateReplacesAllValues(): void
    {
        $this->useCase->execute(null, $this->dto('hello', ['author' => 'Alice', 'rating' => '5', 'tag' => ['php', 'sql']]));
        $itemId = $this->itemRepository->findByCode($this->collectionId, null, 'hello')->id;

        $returnedId = $this->useCase->execute($itemId, $this->dto('hello', ['author' => 'Bob', 'rating' => '9', 'tag' => ['go']]));

        self::assertSame($itemId, $returnedId);

        $map = $this->itemRepository->findWithValues($itemId)->getValuesMap();
        self::assertSame('Bob', $map['author']);
        self::assertSame(9, $map['rating']);
        self::assertSame(['go'], $map['tag']);

        // Full replace: 1 author + 1 rating + 1 tag = 3 value rows, no leftovers.
        self::assertSame(3, Capsule::table('collection_item_values')->where('item_id', $itemId)->count());
    }

    public function testEmptySingleValuesAreNotPersisted(): void
    {
        $this->useCase->execute(null, $this->dto('hello', ['author' => '', 'rating' => '', 'tag' => []]));
        $itemId = $this->itemRepository->findByCode($this->collectionId, null, 'hello')->id;

        self::assertSame(0, Capsule::table('collection_item_values')->where('item_id', $itemId)->count());
    }

    public function testThrowsOnDuplicateCodeInSameSection(): void
    {
        $this->useCase->execute(null, $this->dto('hello', ['author' => 'Alice']));

        $this->expectException(CollectionItemCodeAlreadyExistsException::class);

        $this->useCase->execute(null, $this->dto('hello', ['author' => 'Bob']));
    }

    public function testGeneratesCodeFromNameWhenEmpty(): void
    {
        // dto() names every item "Hello" -> slug "hello".
        $id = $this->useCase->execute(null, $this->dto('', ['author' => 'Alice']));

        self::assertSame('hello', $this->itemRepository->findById($id)->code);
    }

    public function testAppendsNumericSuffixWhenGeneratedCodeIsTaken(): void
    {
        $this->useCase->execute(null, $this->dto('hello', ['author' => 'Alice'])); // occupies "hello"
        $this->useCase->execute(null, $this->dto('', ['author' => 'Bob']));        // -> "hello-2"

        $id = $this->useCase->execute(null, $this->dto('', ['author' => 'Eve']));  // -> "hello-3"

        self::assertSame('hello-3', $this->itemRepository->findById($id)->code);
    }
    public function testAuthorAndEditorAreExplicitAndCreatorIsPreserved(): void
    {
        $id = $this->useCase->execute(null, $this->dto('hello', []), 42);
        $this->useCase->execute($id, $this->dto('hello', []), 43);
        $item = $this->itemRepository->findById($id);
        self::assertSame(42, $item->created_by);
        self::assertSame(43, $item->updated_by);
    }

    public function testMissingUpdateCannotCreateANewRecord(): void
    {
        $this->expectException(CollectionItemFormException::class);
        $this->useCase->execute(999, $this->dto('hello', []));
    }

    public function testCannotMoveAnExistingItemToAnotherCollection(): void
    {
        $id = $this->useCase->execute(null, $this->dto('hello', []));
        $other = Capsule::table('collections')->insertGetId(['code' => 'other', 'name' => 'Other']);
        $this->expectException(CollectionItemFormException::class);
        $this->useCase->execute($id, $this->form(['collectionId' => $other]));
    }

    #[DataProvider('invalidForms')]
    public function testInvalidDataIsRejectedBeforeWriting(array $changes, string $key): void
    {
        try {
            $this->useCase->execute(null, $this->form($changes));
            self::fail('Invalid data was accepted.');
        } catch (CollectionItemFormException $exception) {
            self::assertArrayHasKey($key, $exception->errors);
            self::assertSame(0, Capsule::table('collection_items')->count());
        }
    }

    public static function invalidForms(): array
    {
        return [
            [['name' => '  '], 'name'],
            [['name' => str_repeat('a', 256)], 'name'],
            [['code' => '../escape'], 'code'],
            [['code' => str_repeat('a', 256)], 'code'],
            [['sort' => 'not a number'], 'sort'],
            [['sort' => '2147483648'], 'sort'],
            [['activeFrom' => 'tomorrow'], 'active_from'],
            [['activeFrom' => "2026-11-02\0"], 'active_from'],
            [['previewText' => str_repeat('x', 65536)], 'preview_text'],
            [['activeFrom' => '2026-02-30T12:00'], 'active_from'],
            [['activeFrom' => '2026-12-01T12:00', 'activeTo' => '2026-11-01T12:00'], 'active_to'],
            [['values' => ['rating' => 'abc']], 'values.rating'],
            [['values' => ['rating' => '2.5']], 'values.rating'],
            [['values' => ['author' => ['Alice']]], 'values.author'],
            [['values' => ['tag' => 'php']], 'values.tag'],
            [['values' => ['author' => str_repeat('a', 256)]], 'values.author'],
            [['values' => ['missing' => 'value']], 'values.missing'],
            [['sectionId' => 999], 'section_id'],
            [['collectionId' => 999], 'collection_id'],
        ];
    }

    public function testRequiredValuesAreValidatedOutsideTheController(): void
    {
        Capsule::table('collection_fields')->where('code', 'tag')->update(['required' => true]);
        $this->expectException(CollectionItemFormException::class);
        $this->useCase->execute(null, $this->dto('hello', ['tag' => [' ', '']]));
    }

    public function testRouteCompatibleCodeIsPreserved(): void
    {
        $id = $this->useCase->execute(null, $this->dto('Release.V10+RC_1', []));
        self::assertSame('Release.V10+RC_1', $this->itemRepository->findById($id)->code);
    }

    public function testAnOuterRollbackAlsoRollsBackTheSave(): void
    {
        Capsule::connection()->beginTransaction();
        $this->useCase->execute(null, $this->dto('hello', ['author' => 'Alice']));
        self::assertSame(1, Capsule::connection()->transactionLevel());
        Capsule::connection()->rollBack();
        self::assertSame(0, Capsule::table('collection_items')->count());
        self::assertSame(0, Capsule::table('collection_item_values')->count());
    }

    public function testValueWriteFailureRollsBackItemAndValuesOnlyToTheSavepoint(): void
    {
        $id = $this->useCase->execute(null, $this->dto('hello', ['author' => 'Alice']));
        $values = $this->createMock(ContentCollectionItemValueRepositoryInterface::class);
        $values->method('deleteByItem')->willReturnCallback(static function (int $id): void {
            (new ContentCollectionItemValueRepository())->deleteByItem($id);
        });
        $values->method('insertMany')->willThrowException(new RuntimeException('Interrupted write'));
        $writer = CollectionItemWriterFactory::create($values);
        Capsule::connection()->beginTransaction();
        $this->useCase->execute(null, $this->dto('kept', []));
        try {
            $writer->execute($id, $this->form(['name' => 'Changed', 'values' => ['author' => 'Bob']]));
            self::fail('The write should fail.');
        } catch (RuntimeException $exception) {
            self::assertSame('Interrupted write', $exception->getMessage());
            self::assertSame(1, Capsule::connection()->transactionLevel());
            self::assertSame('Hello', $this->itemRepository->findById($id)->name);
            self::assertSame('Alice', $this->itemRepository->findWithValues($id)->getValuesMap()['author']);
            self::assertNotNull($this->itemRepository->findByCode($this->collectionId, null, 'kept'));
        } finally {
            Capsule::connection()->rollBack();
        }
    }

    #[DataProvider('invalidTypedValues')]
    public function testInvalidTypedValuesAreRejected(string $type, string $value): void
    {
        Capsule::table('collection_fields')->insert(['collection_id' => $this->collectionId, 'code' => 'typed', 'name' => 'Typed', 'type' => $type]);
        $this->expectException(CollectionItemFormException::class);
        $this->useCase->execute(null, $this->dto('hello', ['typed' => $value]));
    }

    public static function invalidTypedValues(): array
    {
        return [['boolean', 'maybe'], ['double', 'NaN'], ['double', '1e9999'], ['integer', '99999999999999999999'],
            ['relation', '-1'], ['date', '2026-02-29'], ['datetime', 'yesterday']];
    }

    public function testTypedDatesAndFalseAreStoredWithoutLosingZero(): void
    {
        foreach (['day' => 'date', 'moment' => 'datetime', 'flag' => 'boolean'] as $code => $type) {
            Capsule::table('collection_fields')->insert(['collection_id' => $this->collectionId, 'code' => $code, 'name' => $code, 'type' => $type]);
        }
        $id = $this->useCase->execute(null, $this->dto('hello', ['day' => '2026-11-02', 'moment' => '2026-11-02T12:30', 'flag' => '0']));
        $values = $this->itemRepository->findWithValues($id)->getValuesMap();
        self::assertSame('2026-11-02 00:00:00', $values['day']);
        self::assertSame('2026-11-02 12:30:00', $values['moment']);
        self::assertFalse($values['flag']);
    }

    public function testNativeOuterTransactionAlsoOwnsTheSave(): void
    {
        $connection = new PdoConnection(Capsule::connection()->getPdo());
        try {
            $connection->transaction(function (): void {
                $this->useCase->execute(null, $this->dto('native', ['author' => 'Alice']));
                throw new RuntimeException('Rollback the outer operation');
            });
        } catch (RuntimeException $exception) {
            self::assertSame('Rollback the outer operation', $exception->getMessage());
        }
        self::assertSame(0, Capsule::table('collection_items')->count());
        self::assertSame(0, Capsule::table('collection_item_values')->count());
    }

    /** @param array<string, mixed> $changes */
    private function form(array $changes = []): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO(...array_replace(get_object_vars($this->dto('hello', [])), $changes));
    }

}
