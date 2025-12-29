<?php

declare(strict_types=1);

namespace Johncms\Container;

use Johncms\System\i18n\Translator;
use Johncms\System\View\Render;
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
        // Add old service instances
        $container->set(Translator::class, di(Translator::class));
        $container->set(Render::class, di(Render::class));

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
