<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Console;

use Johncms\Modules\Mail\Application\Services\MailBbcodeToHtmlConverter;
use Johncms\Modules\Mail\Domain\Models\MailMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mail:convert-bbcode',
    description: 'Convert legacy BBCode mail messages to the CKEditor HTML format',
)]
final class ConvertMailBbcodeCommand extends Command
{
    public function __construct(
        private readonly MailBbcodeToHtmlConverter $converter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'batch-size',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Maximum number of messages to process per chunk',
            default: 500
        );
        $this->addOption(
            name: 'dry-run',
            mode: InputOption::VALUE_NONE,
            description: 'Show how many messages would be converted without writing changes'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = (int) $input->getOption('batch-size');
        $dryRun = (bool) $input->getOption('dry-run');

        if ($batchSize < 1) {
            $io->error('The --batch-size option must be greater than 0.');
            return self::FAILURE;
        }

        $converted = 0;
        $processed = 0;

        MailMessage::query()
            ->orderBy('id')
            ->chunkById($batchSize, function ($messages) use (&$converted, &$processed, $dryRun): void {
                foreach ($messages as $message) {
                    $processed++;
                    $original = (string) $message->getRawOriginal('text');
                    $html = $this->converter->convert($original);
                    if ($html === $original) {
                        continue;
                    }

                    $converted++;
                    if (! $dryRun) {
                        $message->text = $html;
                        $message->save();
                    }
                }
            });

        $io->success(
            sprintf(
                '%s: processed %d messages, %d %s converted.',
                $dryRun ? 'Dry run' : 'Done',
                $processed,
                $converted,
                $dryRun ? 'would be' : 'were'
            )
        );

        return self::SUCCESS;
    }
}
