<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionCodeReservedException;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Application\Services\ReservedCodeCheckerInterface;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveCollectionUseCaseTest extends TestCase
{
    private function cache(bool $expectInvalidate): CollectionCodeCacheInterface&MockObject
    {
        $cache = $this->createMock(CollectionCodeCacheInterface::class);
        $cache->expects($expectInvalidate ? self::once() : self::never())->method('invalidate');

        return $cache;
    }

    private function checker(bool $reserved): ReservedCodeCheckerInterface&MockObject
    {
        $checker = $this->createMock(ReservedCodeCheckerInterface::class);
        $checker->method('isReserved')->willReturn($reserved);

        return $checker;
    }

    private function dto(string $code = 'blog'): CollectionFormDTO
    {
        return new CollectionFormDTO(
            code: $code,
            name: 'Blog',
            description: null,
            active: true,
            public: true,
            sort: 100,
            hasSections: true,
            perPage: 10,
        );
    }

    public function testCreatesNewCollectionWhenCodeIsFree(): void
    {
        $repository = $this->createMock(ContentCollectionRepositoryInterface::class);
        $repository->method('findByCode')->willReturn(null);
        $repository->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $attributes): bool {
                return $attributes['code'] === 'blog'
                    && $attributes['settings'] === ['has_sections' => true, 'per_page' => 10];
            }))
            ->willReturn(new ContentCollection());
        $repository->expects(self::never())->method('update');

        $isUpdate = (new SaveCollectionUseCase($repository, $this->cache(true), $this->checker(false)))->execute(null, $this->dto());

        self::assertFalse($isUpdate);
    }

    public function testUpdatesExistingCollection(): void
    {
        $existing = new ContentCollection(['code' => 'blog']);
        $existing->id = 7;

        $repository = $this->createMock(ContentCollectionRepositoryInterface::class);
        $repository->method('findByCode')->willReturn($existing);
        $repository->method('findById')->with(7)->willReturn($existing);
        $repository->expects(self::once())->method('update')->with(7, self::anything());
        $repository->expects(self::never())->method('create');

        $isUpdate = (new SaveCollectionUseCase($repository, $this->cache(true), $this->checker(false)))->execute(7, $this->dto());

        self::assertTrue($isUpdate);
    }

    public function testThrowsWhenCodeBelongsToAnotherCollection(): void
    {
        $other = new ContentCollection(['code' => 'blog']);
        $other->id = 3;

        $repository = $this->createMock(ContentCollectionRepositoryInterface::class);
        $repository->method('findByCode')->willReturn($other);
        $repository->expects(self::never())->method('create');
        $repository->expects(self::never())->method('update');

        $this->expectException(CollectionCodeAlreadyExistsException::class);

        (new SaveCollectionUseCase($repository, $this->cache(false), $this->checker(false)))->execute(null, $this->dto());
    }

    public function testThrowsWhenCodeIsReserved(): void
    {
        $repository = $this->createMock(ContentCollectionRepositoryInterface::class);
        $repository->expects(self::never())->method('findByCode');
        $repository->expects(self::never())->method('create');
        $repository->expects(self::never())->method('update');

        $this->expectException(CollectionCodeReservedException::class);

        (new SaveCollectionUseCase($repository, $this->cache(false), $this->checker(true)))->execute(null, $this->dto('admin'));
    }
}
