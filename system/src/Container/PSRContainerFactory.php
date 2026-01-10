<?php

declare(strict_types=1);

namespace Johncms\Container;

use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class PSRContainerFactory
{
    private static ?ContainerInterface $containerInstance = null;

    public function __invoke(): ContainerInterface
    {
        $container = new ContainerBuilder();

        $this->loadCoreServices($container);
        $this->loadModuleServices($container);

        $container->compile();

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
        $container->set(ContainerInterface::class, $container);
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
}
