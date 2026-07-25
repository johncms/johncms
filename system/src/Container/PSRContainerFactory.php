<?php

declare(strict_types=1);

namespace Johncms\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class PSRContainerFactory
{
    private static ?ContainerInterface $containerInstance = null;

    public function __invoke(): ContainerInterface
    {
        $cachePath = CACHE_PATH . 'container.php';
        if (CACHE_CONTAINER && file_exists($cachePath)) {
            require_once $cachePath;
            $container = new \ProjectServiceContainer();

            $container->set(PsrContainerInterface::class, $container);

            self::$containerInstance = $container;

            return $container;
        }

        $container = new ContainerBuilder();

        $this->loadCoreServices($container);
        $this->loadModuleServices($container);
        $this->loadOverrideServices($container);

        $container->compile();

        if (CACHE_CONTAINER) {
            $dumper = new PhpDumper($container);
            file_put_contents(
                $cachePath,
                $dumper->dump(
                    [
                        'class'    => 'ProjectServiceContainer',
                        'as_files' => false,
                    ]
                )
            );
        }

        self::$containerInstance = $container;

        return $container;
    }

    public static function getContainer(): ContainerInterface
    {
        if (self::$containerInstance === null) {
            (new self())();
        }
        return self::$containerInstance;
    }

    private function loadCoreServices(ContainerBuilder $container): void
    {
        $container->set(PsrContainerInterface::class, $container);
        $loader = new PhpFileLoader(
            $container,
            new FileLocator(ROOT_PATH . 'system/config')
        );

        $loader->load('services.php');
    }

    private function loadModuleServices(ContainerBuilder $container): void
    {
        foreach (glob(MODULES_PATH . '*/config/services.php') as $file) {
            $loader = new PhpFileLoader(
                $container,
                new FileLocator(\dirname($file))
            );

            $loader->load('services.php');
        }
    }

    private function loadOverrideServices(ContainerBuilder $container): void
    {
        if (! is_file(CONFIG_PATH . 'services.local.php')) {
            return;
        }

        $loader = new PhpFileLoader(
            $container,
            new FileLocator(CONFIG_PATH)
        );

        $loader->load('services.local.php');
    }
}
