<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Console;

use Johncms\Bbcode\BbcodeToHtmlConverter;
use Johncms\Console\OneTimeTaskTracker;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'library:convert-bbcode',
    description: 'One-time: convert legacy BBCode library article texts to the CKEditor HTML format',
)]
final class ConvertLibraryBbcodeCommand extends Command
{
    public function __construct(
        private readonly BbcodeToHtmlConverter $converter,
        private readonly OneTimeTaskTracker $tracker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'batch-size',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Maximum number of articles to process per chunk',
            default: 500
        );
        $this->addOption(
            name: 'dry-run',
            mode: InputOption::VALUE_NONE,
            description: 'Show how many articles would be converted without writing changes'
        );
        $this->addOption(
            name: 'force',
            mode: InputOption::VALUE_NONE,
            description: 'Run again even if this one-time task has already been completed'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = (int) $input->getOption('batch-size');
        $dryRun = (bool) $input->getOption('dry-run');
        $force = (bool) $input->getOption('force');

        if ($batchSize < 1) {
            $io->error('The --batch-size option must be greater than 0.');
            return self::FAILURE;
        }

        if ($this->tracker->isCompleted($this->getName()) && ! $force) {
            $io->warning('This one-time task has already been completed. Use --force to run it again.');
            return self::SUCCESS;
        }

        $converted = 0;
        $processed = 0;

        LibraryText::query()
            ->orderBy('id')
            ->chunkById($batchSize, function ($articles) use (&$converted, &$processed, $dryRun): void {
                foreach ($articles as $article) {
                    $processed++;
                    $original = (string) $article->getRawOriginal('text');
                    $html = $this->converter->convert($original);
                    if ($html === $original) {
                        continue;
                    }

                    $converted++;
                    if (! $dryRun) {
                        $article->text = $html;
                        $article->save();
                    }
                }
            });

        if (! $dryRun) {
            $this->tracker->markCompleted($this->getName());
        }

        $io->success(
            sprintf(
                '%s: processed %d articles, %d %s converted.',
                $dryRun ? 'Dry run' : 'Done',
                $processed,
                $converted,
                $dryRun ? 'would be' : 'were'
            )
        );

        return self::SUCCESS;
    }
}
