<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use FilesystemIterator;
use Johncms\Container\PSRContainerFactory;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionProperty;
use SplFileInfo;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Guards every class asked for with the di() helper.
 *
 * A service the container builds only as part of another one is inlined into it when the
 * container is compiled, and the name then answers to nobody. Injecting it keeps working —
 * that is the case the compiler optimised for — while di() throws, so the failure appears
 * only on the pages that reach the runtime lookup, and only once the definitions around it
 * happen to make the service inlinable. Three storages had gone that way before this test:
 * an Eloquent model cannot be constructor-injected, so it fetches what it needs by name.
 *
 * The fix is to declare such a service public(), next to a comment saying who looks it up.
 *
 * The lookup itself is not the pattern to copy: a class the container builds takes its
 * dependencies through the constructor. This is for the ones it does not — models, the
 * installers, the legacy services older than the container.
 */
final class RuntimeServiceLookupTest extends TestCase
{
    private ?object $previousInstance = null;

    protected function setUp(): void
    {
        $this->previousInstance = $this->containerInstanceProperty()->getValue();
    }

    protected function tearDown(): void
    {
        $this->containerInstanceProperty()->setValue(null, $this->previousInstance);
    }

    public function testEveryServiceLookedUpAtRuntimeIsStillReachableByItsName(): void
    {
        $container = (new PSRContainerFactory())();
        self::assertInstanceOf(ContainerBuilder::class, $container);

        $missing = [];
        foreach ($this->lookedUpServices() as $service => $files) {
            // The definitions, not get(): building a service would need a database this suite
            // does not have, and the question is about the definition anyway.
            if (! $this->isReachableByName($container, $service)) {
                $missing[] = $service . ' (' . implode(', ', $files) . ')';
            }
        }

        sort($missing);

        self::assertSame(
            [],
            $missing,
            "These are fetched with di() but are not public in the container — declare them public():\n"
            . implode("\n", $missing)
        );
    }

    /**
     * Whether a name still answers in the container the dumper writes out.
     *
     * Public is the whole of it: a private definition is inlined into the services that take it
     * and never becomes an entry of its own, however many of them there are. An alias has a
     * visibility of its own, so both ends of one have to be public.
     */
    private function isReachableByName(ContainerBuilder $container, string $service): bool
    {
        if ($container->hasAlias($service)) {
            $alias = $container->getAlias($service);

            return $alias->isPublic() && $this->isReachableByName($container, (string) $alias);
        }

        return $container->hasDefinition($service) && $container->getDefinition($service)->isPublic();
    }

    /**
     * Every class name behind a di() call, with the files that ask for it.
     *
     * @return array<string, list<string>>
     */
    private function lookedUpServices(): array
    {
        $found = [];
        foreach ($this->sourceFiles() as $file) {
            $source = (string) file_get_contents($file);
            foreach ($this->lookupsIn($source) as $service) {
                $found[$service][] = basename($file);
            }
        }

        ksort($found);

        return array_map(array_unique(...), $found);
    }

    /**
     * @return list<string>
     */
    private function lookupsIn(string $source): array
    {
        if (preg_match_all('/\bdi\(\\\\?([A-Za-z_][A-Za-z0-9_\\\\]*)::class\)/', $source, $matches) === 0) {
            return [];
        }

        $imports = $this->importsOf($source);
        $namespace = preg_match('/^namespace\s+([A-Za-z0-9_\\\\]+);/m', $source, $ns) === 1 ? $ns[1] . '\\' : '';

        $services = [];
        foreach ($matches[1] as $name) {
            $services[] = match (true) {
                // Written out in full, or a global class such as PDO.
                str_contains($name, '\\'), ! str_contains($namespace . $name, '\\') => $name,
                isset($imports[$name]) => $imports[$name],
                // Not imported: the name belongs to the namespace of the file itself.
                default => $namespace . $name,
            };
        }

        return array_values(array_unique($services));
    }

    /**
     * @return array<string, string> The local name of an import to the class it stands for.
     */
    private function importsOf(string $source): array
    {
        preg_match_all('/^use\s+([A-Za-z0-9_\\\\]+)(?:\s+as\s+(\w+))?;/m', $source, $matches, PREG_SET_ORDER);

        $imports = [];
        foreach ($matches as $match) {
            $class = $match[1];
            $alias = $match[2] ?? '';
            $imports[$alias !== '' ? $alias : substr((string) strrchr('\\' . $class, '\\'), 1)] = $class;
        }

        return $imports;
    }

    /**
     * @return list<string>
     */
    private function sourceFiles(): array
    {
        $files = [];
        foreach ([ROOT_PATH . 'system' . DS . 'src', MODULES_PATH] as $root) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    private function containerInstanceProperty(): ReflectionProperty
    {
        return (new ReflectionClass(PSRContainerFactory::class))->getProperty('containerInstance');
    }
}
