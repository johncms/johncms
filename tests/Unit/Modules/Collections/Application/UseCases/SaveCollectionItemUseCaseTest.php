<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionItemUseCase;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemValueRepository;
use Johncms\Modules\Collections\Install\Installer;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

final class SaveCollectionItemUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;

    private SaveCollectionItemUseCase $useCase;
    private ContentCollectionItemRepository $itemRepository;
    private int $collectionId;

    protected function setUp(): void
    {
        $this->bootDatabase();
        (new Installer('collections'))->install();

        $fieldRepository = new ContentCollectionFieldRepository();
        $valueRepository = new ContentCollectionItemValueRepository();
        $this->itemRepository = new ContentCollectionItemRepository($fieldRepository, new ContentCollectionItemQueryCompiler());
        $this->useCase = new SaveCollectionItemUseCase($this->itemRepository, $fieldRepository, $valueRepository);

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
}
