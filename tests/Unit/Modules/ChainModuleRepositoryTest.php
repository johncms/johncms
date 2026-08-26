<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use Johncms\Modules\ChainModuleRepository;
use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\ModuleRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * A module can arrive two ways — unpacked into modules/, or installed by Composer into vendor/ —
 * and nothing above this has to know which.
 */
final class ChainModuleRepositoryTest extends TestCase
{
    public function testModulesFromEverySourceAreListedTogether(): void
    {
        $chain = new ChainModuleRepository([
            $this->repository(['johncms/news' => '/app/modules/johncms/news']),
            $this->repository(['vasya/blog' => '/app/vendor/vasya/blog']),
        ]);

        self::assertSame(['johncms/news', 'vasya/blog'], array_keys($chain->all()));
        self::assertSame('/app/vendor/vasya/blog', $chain->find('vasya/blog')?->path);
    }

    /**
     * The same module both ways means somebody unpacked what Composer also installed. The one in
     * modules/ wins: it is the one a person put there deliberately.
     */
    public function testTheFirstSourceWinsWhenTheSameModuleIsInBoth(): void
    {
        $chain = new ChainModuleRepository([
            $this->repository(['vasya/blog' => '/app/modules/vasya/blog']),
            $this->repository(['vasya/blog' => '/app/vendor/vasya/blog']),
        ]);

        self::assertSame('/app/modules/vasya/blog', $chain->find('vasya/blog')?->path);
    }

    public function testForgettingReachesEverySource(): void
    {
        $counter = new class implements ModuleRepositoryInterface {
            public int $forgotten = 0;

            public function all(): array
            {
                return [];
            }

            public function find(string $key): ?ModuleManifest
            {
                return null;
            }

            public function forget(): void
            {
                ++$this->forgotten;
            }
        };

        (new ChainModuleRepository([$counter, $counter]))->forget();

        self::assertSame(2, $counter->forgotten, 'Every source is asked, not only the first.');
    }

    /**
     * @param array<string, string> $modules Key to install path.
     */
    private function repository(array $modules): ModuleRepositoryInterface
    {
        return new class ($modules) implements ModuleRepositoryInterface {
            /** @param array<string, string> $modules */
            public function __construct(private readonly array $modules)
            {
            }

            public function all(): array
            {
                $manifests = [];
                foreach ($this->modules as $key => $path) {
                    $manifests[$key] = new ModuleManifest($key, basename($key), $path, ucfirst(basename($key)));
                }

                return $manifests;
            }

            public function find(string $key): ?ModuleManifest
            {
                return $this->all()[$key] ?? null;
            }

            public function forget(): void
            {
            }
        };
    }
}
