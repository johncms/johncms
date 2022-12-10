<?php

declare(strict_types=1);

namespace Johncms\View\Components;

use Illuminate\View\Component;

abstract class AbstractBladeComponent extends Component
{
    abstract public function render(): string;

    /**
     * Create a Blade view with the raw component string content.
     *
     * @param \Illuminate\Contracts\View\Factory $factory
     * @param string $contents
     * @return string
     */
    protected function createBladeViewFromString($factory, $contents): string
    {
        $factory->addNamespace(
            '__components',
            $directory = CACHE_PATH . 'view'
        );

        if (! is_file($viewFile = $directory . '/' . sha1($contents) . '.blade.php')) {
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($viewFile, $contents);
        }

        return '__components::' . basename($viewFile, '.blade.php');
    }
}
