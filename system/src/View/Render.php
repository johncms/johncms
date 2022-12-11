<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\View;

use Illuminate\Contracts\View\View;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\ViewFinderInterface;
use Johncms\View\Components\Avatar;
use Psr\Container\ContainerInterface;

class Render
{
    private string $theme = 'default';
    private Filesystem $filesystem;
    private Factory $view;

    public function __construct(ContainerInterface $container)
    {
        $this->filesystem = new Filesystem();
        $this->view = new Factory($this->getEngineResolver(), $this->getViewFinder(), new Dispatcher($container));
    }

    private function getEngineResolver(): EngineResolver
    {
        $bladeCompiler = new BladeCompiler(
            files:             $this->filesystem,
            cachePath:         CACHE_PATH . 'view',
            basePath:          '',
            shouldCache:       true,
            compiledExtension: 'php',
        );

        $bladeCompiler->component(Avatar::class, 'avatar');

        $bladeCompilerEngine = new CompilerEngine($bladeCompiler, $this->filesystem);
        $engineResolver = new EngineResolver();
        $engineResolver->register('blade', fn() => $bladeCompilerEngine);
        return $engineResolver;
    }

    private function getViewFinder(): ViewFinderInterface
    {
        $templatePaths = [];
        if ($this->theme !== 'default') {
            $templatePaths[] = THEMES_PATH . $this->theme . '/templates';
        }
        $templatePaths[] = THEMES_PATH . 'default/templates';

        return new FileViewFinder($this->filesystem, $templatePaths);
    }

    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
    }

    public function addData(array $data): static
    {
        $this->view->share($data);
        return $this;
    }

    public function addFolder(string $namespace, string $directory): static
    {
        $this->view->addNamespace($namespace, [
            THEMES_PATH . $this->theme . '/templates/' . $namespace,
            $directory,
        ]);
        return $this;
    }

    public function exists(string $view): bool
    {
        return $this->view->exists($view);
    }

    public function addNamespace(string $namespace, $hints): Factory
    {
        return $this->view->addNamespace($namespace, $hints);
    }

    public function make(string $name, array $data = []): View
    {
        return $this->view->make($name, $data);
    }

    /**
     * We catch exceptions here to avoid doing this in controllers.
     */
    public function render(string $name, array $data = []): string
    {
        return $this->make($name, $data)->render();
    }
}
