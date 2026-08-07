<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\View\Twig\TemplatePathRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Writes ide-twig.json, the file a JetBrains IDE reads to resolve "@namespace/file.twig" into a
 * directory: without it the IDE cannot follow an extends or an include, because the namespaces
 * are a convention of the application rather than anything the IDE can infer.
 *
 * The namespaces come from TemplatePathRegistry, the same source the runtime loader uses, so the
 * file cannot describe a layout the application does not have.
 */
#[AsCommand(
    name: 'twig:ide-config',
    description: 'Write ide-twig.json so the IDE resolves @namespace template names',
)]
final class TwigIdeConfigCommand extends Command
{
    private const FILE = 'ide-twig.json';

    private const DEFAULT_THEME = 'default';

    public function __construct(private readonly TemplatePathRegistry $registry)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'theme',
            null,
            InputOption::VALUE_REQUIRED,
            'Theme whose chain the namespaces are built from',
            self::DEFAULT_THEME
        );
        // The verification gate runs the command in this mode: a module added to the
        // configuration silently breaks template navigation, and nothing else would report it.
        $this->addOption(
            'check',
            null,
            InputOption::VALUE_NONE,
            'Report whether the file is up to date instead of writing it'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = ROOT_PATH . self::FILE;
        $expected = $this->config((string) $input->getOption('theme'));
        $current = is_file($file) ? (string) file_get_contents($file) : null;

        if ($current === $expected) {
            $io->success(sprintf('%s is up to date.', self::FILE));

            return self::SUCCESS;
        }

        if ($input->getOption('check')) {
            $io->error(
                sprintf(
                    '%s is out of date. Regenerate it with "php system/bin/console twig:ide-config".',
                    self::FILE
                )
            );

            return self::FAILURE;
        }

        if (file_put_contents($file, $expected) === false) {
            $io->error(sprintf('Could not write %s.', $file));

            return self::FAILURE;
        }

        $io->success(sprintf('%s written.', self::FILE));

        return self::SUCCESS;
    }

    private function config(string $theme): string
    {
        $namespaces = [];
        foreach ($this->registry->paths($theme) as $namespace => $directories) {
            foreach ($directories as $directory) {
                $namespaces[] = ['namespace' => $namespace, 'path' => $this->relative($directory)];
            }
        }

        $json = json_encode(
            ['namespaces' => $namespaces],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        return $json . PHP_EOL;
    }

    /**
     * A path in the file is resolved against the file itself, which sits in the project root.
     */
    private function relative(string $directory): string
    {
        if (! str_starts_with($directory, ROOT_PATH)) {
            return str_replace(DS, '/', $directory);
        }

        return str_replace(DS, '/', substr($directory, strlen(ROOT_PATH)));
    }
}
