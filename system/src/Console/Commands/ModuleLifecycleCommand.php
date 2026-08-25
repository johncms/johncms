<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Modules\ModuleOperationResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * What every command operating on one module does the same way: take a key, run one operation,
 * print what it did step by step.
 */
abstract class ModuleLifecycleCommand extends Command
{
    protected function report(InputInterface $input, OutputInterface $output, ModuleOperationResult $result): int
    {
        $io = new SymfonyStyle($input, $output);

        foreach ($result->steps() as $step) {
            $marker = match ($step['outcome']) {
                'done'    => '<info>✓</info>',
                'skipped' => '<comment>–</comment>',
                default   => '<error>✗</error>',
            };

            $io->writeln(sprintf(
                ' %s %s%s',
                $marker,
                $step['step'],
                $step['detail'] === null ? '' : ': ' . $step['detail']
            ));
        }

        if (! $result->isSuccessful()) {
            $io->error((string) $result->error());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
