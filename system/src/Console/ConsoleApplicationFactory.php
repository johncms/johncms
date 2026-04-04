<?php

declare(strict_types=1);

namespace Johncms\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

final readonly class ConsoleApplicationFactory
{
    /**
     * @param iterable<Command> $commands
     */
    public function __invoke(iterable $commands): Application
    {
        $application = new Application('JohnCMS', CMS_VERSION);

        foreach ($commands as $command) {
            $application->addCommand($command);
        }

        return $application;
    }
}
