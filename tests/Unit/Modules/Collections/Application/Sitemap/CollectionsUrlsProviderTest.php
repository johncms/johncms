<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Sitemap;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Sitemap\CollectionsUrlsProvider;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionSectionRepository;
use Johncms\Modules\Collections\Install\Installer;
use Johncms\Sitemap\SitemapUrlEntry;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

final class CollectionsUrlsProviderTest extends TestCase
{
    use BootsInMemoryDatabase;

    private CollectionsUrlsProvider $provider;
    private ContentCollectionItemRepositoryInterface $itemRepository;

    protected function setUp(): void
    {
        $this->bootDatabase();
        (new Installer('collections'))->install();

        $fieldRepository = new ContentCollectionFieldRepository();
        $this->itemRepository = new ContentCollectionItemRepository($fieldRepository, new ContentCollectionItemQueryCompiler());
        $this->provider = new CollectionsUrlsProvider(
            new ContentCollectionRepository(),
            new ContentCollectionSectionRepository(),
            $this->itemRepository,
        );

        $blog = $this->insertCollection('blog', true);
        $this->insertCollection('draft', false); // inactive collection -> excluded

        // Active but private collection (no public URL) -> excluded, together with its items.
        $private = $this->insertCollection('storage', true, false);
        $this->insertItem($private, null, 'private-item', true);

        $tech = $this->insertSection($blog, null, 'tech', true);
        $archive = $this->insertSection($blog, null, 'archive', false); // inactive -> excluded
        $this->insertSection($blog, $tech, 'sub', true);

        $this->insertItem($blog, $tech, 'hello', true);
        $this->insertItem($blog, null, 'root-item', true);
        $this->insertItem($blog, null, 'draft-item', false);   // inactive item -> excluded
        $this->insertItem($blog, $archive, 'in-archive', true); // under inactive section -> excluded
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testEmitsRootSectionAndItemUrlsForActiveCollectionsOnly(): void
    {
        $locs = [];
        foreach ($this->provider->getEntries('https://example.test') as $entry) {
            self::assertInstanceOf(SitemapUrlEntry::class, $entry);
            $locs[] = $entry->loc;
        }

        self::assertEqualsCanonicalizing([
            'https://example.test/blog',
            'https://example.test/blog/tech',
            'https://example.test/blog/tech/sub',
            'https://example.test/blog/tech/hello.html',
            'https://example.test/blog/root-item.html',
        ], $locs);
    }

    public function testItemEntriesCarryLastmod(): void
    {
        $lastmods = [];
        foreach ($this->provider->getEntries('https://example.test') as $entry) {
            if (str_ends_with($entry->loc, '.html')) {
                $lastmods[] = $entry->lastmod;
            }
        }

        self::assertNotEmpty($lastmods);
        foreach ($lastmods as $lastmod) {
            self::assertNotNull($lastmod);
            self::assertNotFalse(strtotime((string) $lastmod));
        }
    }

    private function insertCollection(string $code, bool $active, bool $public = true): int
    {
        $now = Carbon::now();

        return Capsule::table('collections')->insertGetId([
            'code' => $code, 'name' => $code, 'sort' => 100, 'active' => $active ? 1 : 0, 'public' => $public ? 1 : 0,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function insertSection(int $collectionId, ?int $parent, string $code, bool $active): int
    {
        $now = Carbon::now();

        return Capsule::table('collection_sections')->insertGetId([
            'collection_id' => $collectionId, 'parent' => $parent, 'name' => $code, 'code' => $code,
            'active' => $active ? 1 : 0, 'sort' => 100, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function insertItem(int $collectionId, ?int $sectionId, string $code, bool $active): int
    {
        $now = Carbon::now();

        return Capsule::table('collection_items')->insertGetId([
            'collection_id' => $collectionId, 'section_id' => $sectionId, 'name' => $code, 'code' => $code,
            'active' => $active ? 1 : 0, 'sort' => 100, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }
}
