<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Console;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Bbcode\BbcodeToHtmlConverter;
use Johncms\Console\OneTimeTaskTracker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'library:convert-comments',
    description: 'One-time: convert legacy BBCode library comments to the CKEditor HTML format',
)]
final class ConvertLibraryCommentsCommand extends Command
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
            description: 'Maximum number of comments to process per chunk',
            default: 500
        );
        $this->addOption(
            name: 'dry-run',
            mode: InputOption::VALUE_NONE,
            description: 'Show how many comments would be converted without writing changes'
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

        Capsule::table('cms_library_comments')
            ->orderBy('id')
            ->chunkById($batchSize, function ($comments) use (&$converted, &$processed, $dryRun): void {
                foreach ($comments as $comment) {
                    $processed++;
                    $originalText = (string) $comment->text;
                    $originalReply = (string) $comment->reply;

                    $text = $this->converter->convert($originalText);
                    $reply = $originalReply === '' ? '' : $this->converter->convert($originalReply);

                    if ($text === $originalText && $reply === $originalReply) {
                        continue;
                    }

                    $converted++;
                    if (! $dryRun) {
                        Capsule::table('cms_library_comments')
                            ->where('id', $comment->id)
                            ->update(['text' => $text, 'reply' => $reply]);
                    }
                }
            });

        if (! $dryRun) {
            $this->tracker->markCompleted($this->getName());
        }

        $io->success(
            sprintf(
                '%s: processed %d comments, %d %s converted.',
                $dryRun ? 'Dry run' : 'Done',
                $processed,
                $converted,
                $dryRun ? 'would be' : 'were'
            )
        );

        return self::SUCCESS;
    }
}
