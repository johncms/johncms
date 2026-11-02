<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Console;

use Johncms\Modules\Collections\Application\Services\CollectionSearchIndexer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'collections:rebuild-search', description: 'Rebuild collection plain-text search documents')]
final class RebuildCollectionSearchCommand extends Command
{
    public function __construct(private readonly CollectionSearchIndexer $index)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('collection', null, InputOption::VALUE_REQUIRED, 'Rebuild only this collection ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getOption('collection');
        if ($id === null) {
            $this->index->rebuildAll();
        } else {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                $io->error('Use a positive collection ID.');
                return self::FAILURE;
            }
            $this->index->rebuildCollection($id);
        }
        $io->success('Collection search documents have been rebuilt.');
        return self::SUCCESS;
    }
}
