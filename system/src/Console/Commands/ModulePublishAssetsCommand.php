<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AsAdminTask;
use Johncms\Modules\ModuleAssetPublisher;
use Johncms\Modules\ModuleRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Copies the assets of the modules into the document root again.
 *
 * Installing a module does this by itself; this is for the times something else has to be put
 * back — a deploy that did not carry public/modules, a directory cleared by hand, a module whose
 * files were replaced without module:update.
 */
#[AsCommand(
    name: 'module:publish-assets',
    description: 'Copy the assets of the installed modules into public/modules',
)]
#[AsAdminTask(
    title: 'Publish the assets of the modules',
    description: 'Copies the styles, scripts and images of the installed modules into the document root.',
)]
final class ModulePublishAssetsCommand extends Command
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleAssetPublisher $publisher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('module', InputArgument::OPTIONAL, 'Key of one module; all of them by default')
            ->addOption(
                'symlink',
                null,
                InputOption::VALUE_NONE,
                'Link the directory instead of copying it — for developing a module, not for a live site'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $only = $input->getArgument('module');
        $symlink = $input->getOption('symlink') === true;

        $published = 0;
        $modules = 0;

        foreach ($this->registry->enabled() as $key => $manifest) {
            if (is_string($only) && $only !== '' && $only !== $key) {
                continue;
            }

            $files = $this->publisher->publish($manifest, $symlink);
            if ($files === 0) {
                continue;
            }

            ++$modules;
            $published += $files;
            $io->writeln(sprintf(' <info>%s</info> → public/modules/%s', $key, $manifest->alias));
        }

        if ($modules === 0) {
            $io->warning('None of the modules ship assets.');

            return self::SUCCESS;
        }

        $io->success(sprintf('%d files of %d modules published.', $published, $modules));

        return self::SUCCESS;
    }
}
