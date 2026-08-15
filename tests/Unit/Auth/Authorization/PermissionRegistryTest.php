<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use InvalidArgumentException;
use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Auth\Authorization\PermissionRegistry;
use PHPUnit\Framework\TestCase;

final class PermissionRegistryTest extends TestCase
{
    public function testAnEmptyRegistryKnowsNothing(): void
    {
        $registry = new PermissionRegistry();

        self::assertSame([], $registry->all());
        self::assertFalse($registry->has('forum.topic.delete'));
    }

    /**
     * The whole point of the second constructor argument: a test declares what it needs without
     * a container or a tagged provider behind it.
     */
    public function testDefinitionsCanBePassedDirectly(): void
    {
        $registry = new PermissionRegistry(definitions: [
            new PermissionDefinition('forum.topic.delete', 'forum', 'Delete topics'),
        ]);

        self::assertTrue($registry->has('forum.topic.delete'));
        self::assertSame('Delete topics', $registry->get('forum.topic.delete')->label);
    }

    public function testProvidersAreCollected(): void
    {
        $registry = new PermissionRegistry([
            $this->provider(new PermissionDefinition('forum.topic.delete', 'forum', 'Delete topics')),
            $this->provider(new PermissionDefinition('news.article.edit', 'news', 'Edit articles')),
        ]);

        self::assertTrue($registry->has('forum.topic.delete'));
        self::assertTrue($registry->has('news.article.edit'));
    }

    public function testGroupedKeepsDeclarationOrderWithinAGroup(): void
    {
        $registry = new PermissionRegistry([
            $this->provider(
                new PermissionDefinition('forum.topic.view', 'forum', 'View topics'),
                new PermissionDefinition('forum.topic.delete', 'forum', 'Delete topics'),
            ),
            $this->provider(new PermissionDefinition('news.article.edit', 'news', 'Edit articles')),
        ]);

        $grouped = $registry->grouped();

        self::assertSame(['forum', 'news'], array_keys($grouped));
        self::assertSame(
            ['forum.topic.view', 'forum.topic.delete'],
            array_map(static fn (PermissionDefinition $d): string => $d->key, $grouped['forum'])
        );
    }

    /**
     * The editor prints a heading above every group. A module that never named its group is still
     * listed, under its own key, rather than under nothing at all.
     */
    public function testAGroupIsTitledByTheModuleOrByItsKey(): void
    {
        $registry = new PermissionRegistry([
            $this->provider(
                new PermissionDefinition('forum.topic.view', 'forum', 'View topics'),
                new PermissionDefinition('forum.topic.delete', 'forum', 'Delete topics', 'Forum'),
            ),
            $this->provider(new PermissionDefinition('news.article.edit', 'news', 'Edit articles')),
        ]);

        self::assertSame(['forum' => 'Forum', 'news' => 'news'], $registry->groupLabels());
    }

    public function testUnknownPermissionIsAnError(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PermissionRegistry())->get('forum.topic.delete');
    }

    public function testProvidersAreReadOnlyOnce(): void
    {
        $provider = new CountingPermissionProvider();
        $registry = new PermissionRegistry([$provider]);

        $registry->all();
        $registry->all();
        $registry->has('forum.topic.delete');

        self::assertSame(1, $provider->calls);
    }

    private function provider(PermissionDefinition ...$definitions): PermissionProviderInterface
    {
        return new class ($definitions) implements PermissionProviderInterface {
            /**
             * @param list<PermissionDefinition> $definitions
             */
            public function __construct(private readonly array $definitions)
            {
            }

            public function permissions(): iterable
            {
                return $this->definitions;
            }
        };
    }
}
