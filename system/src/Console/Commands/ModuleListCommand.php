<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleState;
use Johncms\Modules\ModuleStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'module:list',
    description: 'Show every module this site knows of and what state it is in',
)]
final class ModuleListCommand extends Command
{
    public function __construct(private readonly ModuleRegistry $registry)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'problems',
            mode: InputOption::VALUE_NONE,
            description: 'Only the modules something is wrong with'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $states = $this->registry->states();
        if ($input->getOption('problems') === true) {
            $states = array_values(array_filter(
                $states,
                static fn (ModuleState $state): bool => $state->status === ModuleStatus::Broken
            ));
        }

        if ($states === []) {
            $io->warning('No modules found.');

            return self::SUCCESS;
        }

        $io->table(
            ['Key', 'Alias', 'Version', 'Status', 'Name'],
            array_map(
                static fn (ModuleState $state): array => [
                    $state->key,
                    $state->alias,
                    $state->version ?? CMS_VERSION,
                    $state->system && $state->status === ModuleStatus::Enabled
                        ? $state->status->value . ' (system)'
                        : $state->status->value,
                    $state->name,
                ],
                $states
            )
        );

        foreach ($states as $state) {
            if ($state->problem !== null) {
                $io->warning(sprintf('%s: %s', $state->key, $state->problem));
            }
        }

        return self::SUCCESS;
    }
}
