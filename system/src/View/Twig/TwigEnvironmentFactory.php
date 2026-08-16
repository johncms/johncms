<?php

declare(strict_types=1);

namespace Johncms\View\Twig;

use Johncms\View\ViewEnvironment;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\ContainerRuntimeLoader;

/**
 * Builds a Twig environment and hands it over ready: the namespaces are known from the installed
 * modules and the theme chain, so nothing is added to it afterwards. An environment that is never
 * mutated can be shared by every request of a process without carrying anything between them.
 */
final readonly class TwigEnvironmentFactory
{
    public function __construct(
        private TemplatePathRegistry $pathRegistry,
        private ContainerInterface $container,
        private bool $debug = DEBUG,
    ) {
    }

    /**
     * @param iterable<ExtensionInterface> $extensions
     */
    public function create(ViewEnvironment $environment, iterable $extensions = []): Environment
    {
        $loader = new FilesystemLoader();
        foreach ($this->pathRegistry->paths((string) config('johncms.skindef', 'default')) as $namespace => $paths) {
            foreach ($paths as $path) {
                $loader->addPath($path, $namespace);
            }
        }

        $twig = new Environment($loader, [
            // Escaping follows the format the file name announces: `page.twig` is HTML and is
            // escaped as such, while the text form of an email — `registration.txt.twig` — is not
            // markup and must reach the reader as it was written, ampersands and all.
            'autoescape' => 'name',
            'cache'      => CACHE_PATH . 'twig' . DS . $environment->value,
            'debug'      => $this->debug,
            // Reading a template that was never assigned is a bug, and in development it should
            // be the developer who sees it rather than a visitor of a live site.
            'strict_variables' => $this->debug,
            'auto_reload'      => $this->debug,
        ]);

        // An extension declares its functions, the runtime object behind them is built by the
        // container only if a template actually calls one.
        $twig->addRuntimeLoader(new ContainerRuntimeLoader($this->container));

        if ($this->debug) {
            $twig->addExtension(new DebugExtension());
        }

        foreach ($extensions as $extension) {
            $twig->addExtension($extension);
        }

        return $twig;
    }
}
