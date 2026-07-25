<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Johncms\Container\PSRContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;

/**
 * Guards the container definitions themselves.
 *
 * Every class under system/src and the module src directories is autowired by a directory-wide
 * load(), so a new class with a scalar constructor argument (an exception, a DTO) breaks the
 * compilation of the whole container and takes the site down. Nothing else in the gate catches
 * that: the code is valid PHP, passes the coding standard, static analysis and every other test.
 *
 * Relies on CACHE_CONTAINER being false (config/constants.php): with a dumped container the
 * factory would return the cached one and nothing would be compiled.
 *
 * Stays in the unit suite on purpose: it needs no database, and CI runs `composer test:unit`, so
 * this is the only place where a broken container definition fails the pipeline.
 */
final class ContainerCompilationTest extends TestCase
{
    private ?ContainerInterface $previousInstance = null;

    protected function setUp(): void
    {
        $this->previousInstance = $this->containerInstanceProperty()->getValue();
    }

    protected function tearDown(): void
    {
        // The factory writes the built container into a private static property. Restore it,
        // otherwise this test would leave a fully built container behind for every later test.
        $this->containerInstanceProperty()->setValue(null, $this->previousInstance);
    }

    public function testTheServiceContainerCompiles(): void
    {
        $container = (new PSRContainerFactory())();

        self::assertInstanceOf(ContainerInterface::class, $container);
    }

    private function containerInstanceProperty(): \ReflectionProperty
    {
        return (new ReflectionClass(PSRContainerFactory::class))->getProperty('containerInstance');
    }
}
