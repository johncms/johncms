<?php

declare(strict_types=1);

namespace Johncms\Container;

use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class PSRContainerFactory
{
    /**
     * @throws \Exception
     */
    public function __invoke(): ContainerInterface
    {
        $container = new ContainerBuilder();

        $this->loadCoreServices($container);
        $this->loadModuleServices($container);

        $container->compile();

        return $container;
    }

    private function loadCoreServices(ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader(
            $container,
            new FileLocator(__DIR__ . '/config')
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
