<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\View\Twig\TemplateFinder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;
use Twig\Error\Error;

/**
 * Warms the template cache, so the first visitor after a deployment is served from compiled
 * templates instead of paying for compiling them.
 */
#[AsCommand(
    name: 'twig:compile',
    description: 'Compile the Twig templates of the active theme into the cache',
)]
final class TwigCompileCommand extends Command
{
    public function __construct(
        private readonly Environment $twig,
        private readonly TemplateFinder $templates,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $errors = [];
        $compiled = 0;

        foreach ($this->templates->names() as $name) {
            try {
                $this->twig->load($name);
                $compiled++;
            } catch (Error $error) {
                $errors[] = sprintf('%s: %s', $name, $error->getRawMessage());
            }
        }

        if ($errors !== []) {
            $io->error(sprintf('%d templates failed to compile:', count($errors)));
            $io->listing($errors);

            return self::FAILURE;
        }

        $io->success(sprintf('%d templates compiled.', $compiled));

        return self::SUCCESS;
    }
}
