<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Infrastructure\Persistence\Repository;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionSectionRepository;
use Johncms\Modules\Collections\Install\Installer;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

final class ContentCollectionSectionRepositoryTest extends TestCase
{
    use BootsInMemoryDatabase;

    private ContentCollectionSectionRepository $repository;
    private int $collectionId;
    private int $sectionA;

    protected function setUp(): void
    {
        $this->bootDatabase();
        (new Installer('collections'))->install();
        $this->repository = new ContentCollectionSectionRepository();

        $now = Carbon::now();
        $this->collectionId = Capsule::table('collections')->insertGetId([
            'code' => 'blog', 'name' => 'Blog', 'sort' => 100, 'active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->sectionA = $this->insert(null, 'a', 200, true);
        $this->insert(null, 'b', 100, true);
        $this->insert(null, 'c', 300, false);      // inactive root
        $this->insert($this->sectionA, 'a1', 100, true);
        $this->insert($this->sectionA, 'a2', 200, false); // inactive child
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testGetActiveChildrenAtRootExcludesInactiveAndOrdersBySort(): void
    {
        $codes = $this->repository->getActiveChildren($this->collectionId, null)->pluck('code')->all();

        // b (sort 100) before a (sort 200); inactive c excluded.
        self::assertSame(['b', 'a'], $codes);
    }

    public function testGetActiveChildrenScopedToParent(): void
    {
        $codes = $this->repository->getActiveChildren($this->collectionId, $this->sectionA)->pluck('code')->all();

        self::assertSame(['a1'], $codes);
    }

    private function insert(?int $parent, string $code, int $sort, bool $active): int
    {
        $now = Carbon::now();

        return Capsule::table('collection_sections')->insertGetId([
            'collection_id' => $this->collectionId,
            'parent'        => $parent,
            'name'          => $code,
            'code'          => $code,
            'active'        => $active ? 1 : 0,
            'sort'          => $sort,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }
}
