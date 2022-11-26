<?php

declare(strict_types=1);

namespace Johncms\Modules;

use Illuminate\Support\Str;

class ComposerOutputParser
{
    public function __construct(
        private string $consoleOutput
    ) {
    }

    /**
     * Check if a module has been installed
     */
    public function moduleInstall(string $moduleName): bool
    {
        return Str::containsAll(
            $this->consoleOutput,
            [
                '- Installing ' . $moduleName,
                'Generating autoload files',
            ],
            true
        );
    }
}
