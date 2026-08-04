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
use Twig\Source;

/**
 * Parses every template and reports the ones that do not compile.
 *
 * Nothing else in the verification gate covers templates: a syntax error in one is invisible to
 * the coding standard, to static analysis and to the tests, and shows up as a broken page.
 */
#[AsCommand(
    name: 'twig:lint',
    description: 'Check the syntax of all Twig templates',
)]
final class TwigLintCommand extends Command
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
        $checked = 0;

        foreach ($this->templates->all() as $file) {
            $checked++;
            $code = (string) file_get_contents($file);

            try {
                $this->twig->parse($this->twig->tokenize(new Source($code, $file, $file)));
            } catch (Error $error) {
                $errors[] = sprintf('%s:%d %s', $file, $error->getLine(), $error->getRawMessage());
            }
        }

        if ($errors !== []) {
            $io->error(sprintf('%d of %d templates failed to compile:', count($errors), $checked));
            $io->listing($errors);

            return self::FAILURE;
        }

        $io->success(sprintf('%d templates are valid.', $checked));

        return self::SUCCESS;
    }
}
