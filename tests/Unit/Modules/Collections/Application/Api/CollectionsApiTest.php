<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Api;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionsApi;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionItemUseCase;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemValueRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

final class CollectionsApiTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private CollectionsApi $api;
    private int $collectionId;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('collections');

        $fieldRepository = new ContentCollectionFieldRepository();
        $valueRepository = new ContentCollectionItemValueRepository();
        $itemRepository = new ContentCollectionItemRepository($fieldRepository, new ContentCollectionItemQueryCompiler());
        $collectionRepository = new ContentCollectionRepository();

        $this->api = new CollectionsApi(
            $collectionRepository,
            $itemRepository,
            new SaveCollectionItemUseCase($itemRepository, $fieldRepository, $valueRepository),
            new DeleteCollectionItemUseCase($itemRepository),
        );

        $now = date('Y-m-d H:i:s');
        $this->collectionId = Capsule::table('collections')->insertGetId([
            'code' => 'blog', 'name' => 'Blog', 'sort' => 100, 'active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $fieldRepository->create(['collection_id' => $this->collectionId, 'code' => 'author', 'name' => 'Author', 'type' => 'string', 'required' => false, 'multiple' => false, 'sort' => 100]);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    /**
     * @param array<string, string|list<string>> $values
     */
    private function dto(string $code, array $values = []): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO(
            collectionId: $this->collectionId,
            sectionId: null,
            code: $code,
            name: 'Hello ' . $code,
            active: true,
            activeFrom: null,
            activeTo: null,
            sort: 100,
            previewText: null,
            detailText: null,
            values: $values,
        );
    }

    public function testFindCollectionResolvesByCode(): void
    {
        self::assertSame($this->collectionId, $this->api->findCollection('blog')?->id);
        self::assertNull($this->api->findCollection('missing'));
    }

    public function testAddItemReturnsIdAndPersistsValues(): void
    {
        $id = $this->api->addItem($this->dto('hello', ['author' => 'Alice']));

        $item = $this->api->getItem($id);
        self::assertNotNull($item);
        self::assertSame('hello', $item->code);
        self::assertSame('Alice', $item->getValuesMap()['author']);
    }

    public function testAddItemRejectsDuplicateCode(): void
    {
        $this->api->addItem($this->dto('hello'));

        $this->expectException(CollectionItemCodeAlreadyExistsException::class);
        $this->api->addItem($this->dto('hello'));
    }

    public function testUpdateItemRebuildsValues(): void
    {
        $id = $this->api->addItem($this->dto('hello', ['author' => 'Alice']));

        $this->api->updateItem($id, $this->dto('hello', ['author' => 'Bob']));

        self::assertSame('Bob', $this->api->getItem($id)?->getValuesMap()['author']);
    }

    public function testDeleteItemRemovesItAndValues(): void
    {
        $id = $this->api->addItem($this->dto('hello', ['author' => 'Alice']));

        $this->api->deleteItem($id);

        self::assertNull($this->api->getItem($id));
        self::assertSame(0, Capsule::table('collection_item_values')->where('item_id', $id)->count());
    }

    public function testGetItemsAndCountHonorQuery(): void
    {
        $this->api->addItem($this->dto('one'));
        $this->api->addItem($this->dto('two'));

        $query = new ContentCollectionItemQuery(collectionId: $this->collectionId);

        self::assertSame(2, $this->api->countItems($query));
        self::assertCount(2, $this->api->getItems($query));
    }
}
