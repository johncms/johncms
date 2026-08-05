<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Carbon\Carbon;
use HTMLPurifier;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Services\ItemContentFormatter;
use Johncms\Modules\Collections\Application\UseCases\GetPublicItemUseCase;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Install\Installer;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

final class GetPublicItemUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;

    private GetPublicItemUseCase $useCase;
    private int $collectionId;

    protected function setUp(): void
    {
        $this->bootDatabase();
        (new Installer('collections'))->install();

        $fieldRepository = new ContentCollectionFieldRepository();
        $itemRepository = new ContentCollectionItemRepository($fieldRepository, new ContentCollectionItemQueryCompiler());

        // Passthrough purifier: this test asserts value mapping, not sanitization.
        $purifier = $this->createMock(HTMLPurifier::class);
        $purifier->method('purify')->willReturnArgument(0);
        $formatter = new ItemContentFormatter($purifier);

        $this->useCase = new GetPublicItemUseCase($itemRepository, $fieldRepository, $formatter);

        $now = Carbon::now();
        $this->collectionId = Capsule::table('collections')->insertGetId([
            'code' => 'blog', 'name' => 'Blog', 'sort' => 100, 'active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $authorId = Capsule::table('collection_fields')->insertGetId([
            'collection_id' => $this->collectionId, 'code' => 'author', 'name' => 'Author', 'type' => 'string',
            'required' => 0, 'multiple' => 0, 'sort' => 100, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $tagId = Capsule::table('collection_fields')->insertGetId([
            'collection_id' => $this->collectionId, 'code' => 'tag', 'name' => 'Tag', 'type' => 'string',
            'required' => 0, 'multiple' => 1, 'sort' => 200, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $visibleId = $this->insertItem('hello', ['preview_text' => 'Intro', 'detail_text' => 'Body']);
        $this->insertItem('draft', ['active' => 0]);
        $this->insertItem('future', ['active_from' => $now->copy()->addDay()]);

        Capsule::table('collection_item_values')->insert([
            $this->valueRow($visibleId, $authorId, ['value_string' => 'Alice'], 0),
            $this->valueRow($visibleId, $tagId, ['value_string' => 'php'], 0),
            $this->valueRow($visibleId, $tagId, ['value_string' => 'sql'], 1),
        ]);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testReturnsNullWhenItemMissing(): void
    {
        self::assertNull($this->useCase->execute($this->collectionId, null, 'nope'));
    }

    public function testReturnsNullForInactiveOrFutureItem(): void
    {
        self::assertNull($this->useCase->execute($this->collectionId, null, 'draft'));
        self::assertNull($this->useCase->execute($this->collectionId, null, 'future'));
    }

    public function testReturnsDetailWithLabeledValues(): void
    {
        $detail = $this->useCase->execute($this->collectionId, null, 'hello');

        self::assertNotNull($detail);
        self::assertSame('hello', $detail->name);
        self::assertSame('Intro', (string) $detail->previewText);
        self::assertSame('Body', (string) $detail->detailText);

        self::assertSame(
            [
                ['label' => 'Author', 'values' => ['Alice']],
                ['label' => 'Tag', 'values' => ['php', 'sql']],
            ],
            $detail->values
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertItem(string $code, array $overrides = []): int
    {
        $now = Carbon::now();

        return Capsule::table('collection_items')->insertGetId(array_merge([
            'collection_id' => $this->collectionId,
            'section_id'    => null,
            'name'          => $code,
            'code'          => $code,
            'active'        => 1,
            'active_from'   => null,
            'active_to'     => null,
            'sort'          => 100,
            'created_at'    => $now,
            'updated_at'    => $now,
        ], $overrides));
    }

    /**
     * @param array<string, mixed> $columns
     * @return array<string, mixed>
     */
    private function valueRow(int $itemId, int $fieldId, array $columns, int $sort): array
    {
        return array_merge([
            'item_id'      => $itemId,
            'field_id'     => $fieldId,
            'value_string' => null,
            'value_int'    => null,
            'value_double' => null,
            'value_date'   => null,
            'value_text'   => null,
            'sort'         => $sort,
        ], $columns);
    }
}
