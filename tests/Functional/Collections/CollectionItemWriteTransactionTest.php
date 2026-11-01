<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Services\CollectionItemValidator;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionItemUseCase;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemValueRepositoryInterface;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemValueRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionSectionRepository;
use Johncms\Validator\ValidatorInterface;
use RuntimeException;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;

final class CollectionItemWriteTransactionTest extends FunctionalTestCase
{
    public function testSaveCanBeRolledBackByItsCaller(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'atomic', 'name' => 'Atomic']);
        $form = new CollectionItemFormDTO($collection, null, 'original', 'Atomic', true, null, null, 100, null, null, []);
        Capsule::connection()->beginTransaction();
        $id = $this->container()->get(SaveCollectionItemUseCase::class)->execute(null, $form);
        Capsule::connection()->rollBack();
        self::assertFalse(Capsule::table('collection_items')->where('id', $id)->exists());
    }

    public function testFailedValueWriteDoesNotChangeItemAndDoesNotCommitTheCaller(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'atomic', 'name' => 'Atomic']);
        Fixture::insert('collection_fields', ['collection_id' => $collection, 'code' => 'author', 'name' => 'Author', 'type' => 'string']);
        $id = $this->container()->get(SaveCollectionItemUseCase::class)->execute(null, $this->form($collection, 'original'));
        $values = $this->createMock(ContentCollectionItemValueRepositoryInterface::class);
        $values->method('deleteByItem')->willReturnCallback(static function (int $id): void {
            (new ContentCollectionItemValueRepository())->deleteByItem($id);
        });
        $values->method('insertMany')->willThrowException(new RuntimeException('Interrupted write'));
        $fields = new ContentCollectionFieldRepository();
        $writer = new SaveCollectionItemUseCase(
            new ContentCollectionItemRepository($fields, new ContentCollectionItemQueryCompiler()), $fields, $values,
            new ContentCollectionRepository(), new ContentCollectionSectionRepository(), new CollectionTransaction(),
            new CollectionItemValidator($this->container()->get(ValidatorInterface::class))
        );
        $depth = Capsule::connection()->transactionLevel();
        try {
            $writer->execute($id, $this->form($collection, 'changed'));
            self::fail('The save should fail.');
        } catch (RuntimeException $exception) {
            self::assertSame('Interrupted write', $exception->getMessage());
        }
        self::assertSame($depth, Capsule::connection()->transactionLevel());
        self::assertSame('original', Capsule::table('collection_items')->where('id', $id)->value('code'));
        self::assertSame('original', Capsule::table('collection_item_values')->where('item_id', $id)->value('value_string'));
    }

    private function form(int $collection, string $code): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO($collection, null, $code, 'Atomic', true, null, null, 100, null, null, ['author' => $code]);
    }
}
