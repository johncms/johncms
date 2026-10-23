<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Johncms\Http\CachedImageResponse;
use Johncms\Http\Request;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The dependency rules of .agents/architecture.md, checked by PHPStan on every run through phpat.
 *
 * A rule here fails the gate for a dependency added today. The ones the code already had when the
 * rule arrived are accepted by phpstan-baseline.neon, like the rest of the legacy debt: paying one
 * off is removing it from the baseline.
 *
 * The modules are the bundled ones, read from the autoload section of composer.json, and what each
 * of them is allowed comes from its own manifest — a module added tomorrow is held to the same
 * rules without a line changed here.
 */
final class ArchitectureTest
{
    private const string GUIDE = 'See .agents/architecture.md, "Dependency Rules".';

    /**
     * A module can be switched off, and the container is compiled from the modules that are on:
     * a core service built against a module stops the whole site from booting without it.
     */
    public function testTheCoreDependsOnNoModule(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::withFilepath('#/system/src/#', true))
            ->shouldNot()
            ->dependOn()
            ->classes(...self::namespaces(array_keys(self::modules())))
            ->because('A module can be switched off, and the core has to boot without it. ' . self::GUIDE);
    }

    /**
     * A module that is built against another one cannot be loaded without it. It says so in the
     * requires of its manifest, which is what keeps the other one from being switched off under it;
     * a system module is always there.
     *
     * @return iterable<string, Rule>
     */
    public function testEveryModuleDependsOnlyOnWhatItRequires(): iterable
    {
        $modules = self::modules();

        foreach ($modules as $key => $module) {
            $forbidden = array_values(array_filter(
                array_keys($modules),
                static fn (string $other): bool => $other !== $key
                    && ! $modules[$other]['system']
                    && ! in_array($other, $module['requires'], true)
            ));

            // The name ends up in the identifier of a finding, which takes letters and digits only.
            yield 'In' . ucfirst($module['alias']) => PHPat::rule()
                ->classes(Selector::inNamespace($module['namespace']))
                ->shouldNot()
                ->dependOn()
                ->classes(...self::namespaces($forbidden))
                ->because(sprintf(
                    'Declare the module in the requires of %s/module.php, or reach it through an extension point. %s',
                    $key,
                    self::GUIDE
                ));
        }
    }

    /**
     * Controllers and middleware turn a request into calls on the layers below and the result back
     * into a response. Below them nothing knows about HTTP: an upload is mapped to a plain DTO.
     */
    public function testTheDomainAndTheApplicationStayOutOfHttp(): Rule
    {
        return PHPat::rule()
            ->classes(...self::layers('Domain', 'Application'))
            ->excluding(Selector::inNamespace('/\\\\(Controllers|Middlewares?)(\\\\|$)/', true))
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::classname(Request::class),
                Selector::classname(CachedImageResponse::class),
                Selector::inNamespace('Symfony\Component\HttpFoundation'),
                Selector::inNamespace('Psr\Http\Message'),
            )
            ->because('HTTP types stay in controllers, middleware and the HTTP layer. ' . self::GUIDE);
    }

    public function testTheDomainAndTheApplicationDependOnNoInfrastructure(): Rule
    {
        return PHPat::rule()
            ->classes(...self::layers('Domain', 'Application'))
            ->shouldNot()
            ->dependOn()
            ->classes(...self::layers('Infrastructure'))
            ->because('They take the interfaces the Domain declares, and the container wires the implementations. ' . self::GUIDE);
    }

    /**
     * The stack is the last resort of a core service whose callers have no request to pass. In a
     * module it is a request captured beyond its cycle in a different shape.
     */
    public function testAModuleReadsNoRequestOffTheStack(): Rule
    {
        return PHPat::rule()
            ->classes(...self::namespaces(array_keys(self::modules())))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::classname(RequestStack::class))
            ->because('A controller takes the Request as an argument of its action. See .agents/architecture.md, "How a class gets the current request".');
    }

    public function testARepositoryContractIsDeclaredInTheDomain(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::AnyOf(...self::namespaces(array_keys(self::modules()))),
                Selector::isInterface(),
                Selector::classname('/RepositoryInterface$/', true),
            ))
            ->excluding(...self::layers('Domain'))
            ->shouldNotExist()
            ->because('Repository contracts are declared in the Domain as *RepositoryInterface. ' . self::GUIDE);
    }

    public function testARepositoryIsImplementedInTheInfrastructure(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::AnyOf(...self::namespaces(array_keys(self::modules()))),
                Selector::implements('/RepositoryInterface$/', true),
            ))
            ->excluding(...self::layers('Infrastructure'))
            ->shouldNotExist()
            ->because('A repository contract is implemented in the Infrastructure. ' . self::GUIDE);
    }

    /**
     * The bundled modules by key: the namespace composer.json maps onto them, whether the manifest
     * marks them as system ones, and the modules it requires.
     *
     * @return array<string, array{namespace: string, alias: string, system: bool, requires: list<string>}>
     */
    private static function modules(): array
    {
        static $modules = null;

        if ($modules !== null) {
            return $modules;
        }

        $root = dirname(__DIR__, 2);
        $composer = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $modules = [];

        foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
            if (preg_match('#^modules/([^/]+/[^/]+)/src/?$#', $path, $matches) !== 1) {
                continue;
            }

            $manifest = require $root . '/modules/' . $matches[1] . '/module.php';

            $modules[$manifest['key']] = [
                'namespace' => rtrim($namespace, '\\'),
                'alias'     => $manifest['alias'],
                'system'    => ($manifest['system'] ?? false) === true,
                'requires'  => array_keys($manifest['requires']['modules'] ?? []),
            ];
        }

        return $modules;
    }

    /**
     * @param list<string> $keys
     * @return list<SelectorInterface>
     */
    private static function namespaces(array $keys): array
    {
        $modules = self::modules();

        return array_values(array_map(
            static fn (string $key): SelectorInterface => Selector::inNamespace($modules[$key]['namespace']),
            $keys
        ));
    }

    /**
     * The given layers of every module.
     *
     * @return list<SelectorInterface>
     */
    private static function layers(string ...$layers): array
    {
        $selectors = [];

        foreach (self::modules() as $module) {
            foreach ($layers as $layer) {
                $selectors[] = Selector::inNamespace($module['namespace'] . '\\' . $layer);
            }
        }

        return $selectors;
    }
}
