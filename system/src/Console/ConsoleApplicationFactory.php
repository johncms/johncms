<?php

declare(strict_types=1);

namespace Johncms\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The console, with every command listed but none of them built.
 *
 * A command is constructed when it is run, so starting the console costs nothing beyond the
 * command asked for: twig:lint or cache:clear work on a site whose database is down, because the
 * commands that need one are never built for them. The list comes from AddConsoleCommandPass,
 * which reads the name and the description of #[AsCommand] at compile time.
 */
final readonly class ConsoleApplicationFactory
{
    public function __invoke(CommandLoaderInterface $commandLoader, ContainerInterface $container): Application
    {
        $application = new Application('JohnCMS', CMS_VERSION);
        $application->setCommandLoader($commandLoader);

        // A command that names itself in configure() instead of #[AsCommand] cannot be listed
        // before it is built, so it is built on every start of the console — together with
        // whatever it depends on.
        /** @var list<string> $eagerCommandIds */
        $eagerCommandIds = $container->getParameter('console.command.ids');

        foreach ($eagerCommandIds as $id) {
            $command = $container->get($id);

            if ($command instanceof Command) {
                $application->addCommand($command);
            }
        }

        return $application;
    }
}
