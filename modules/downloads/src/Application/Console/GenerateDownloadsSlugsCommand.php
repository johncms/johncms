<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Console;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Johncms\Console\OneTimeTaskTracker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'downloads:generate-slugs',
    description: 'One-time: add slug columns and generate slugs for download categories and files',
)]
final class GenerateDownloadsSlugsCommand extends Command
{
    private const RESERVED_CATEGORY_SLUGS = [
        'new',
        'top',
        'search',
        'favorites',
        'user-files',
        'load',
        'comments',
        'upload',
        'moderation',
        'edit-file',
        'delete-file',
        'edit-screen',
        'additional-files',
        'move-file',
        'import',
        'scan-dir',
        'recount',
        'top-users',
        'comments-review',
        'categories',
    ];

    public function __construct(
        private readonly OneTimeTaskTracker $tracker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'force',
            mode: InputOption::VALUE_NONE,
            description: 'Run again even if this one-time task has already been completed'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');

        if ($this->tracker->isCompleted($this->getName()) && ! $force) {
            $io->warning('This one-time task has already been completed. Use --force to run it again.');
            return self::SUCCESS;
        }

        $schema = Capsule::schema();
        $connection = Capsule::connection();

        // --- download__category ---

        $schema->table('download__category', static function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('download__category', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
        });

        $categories = $connection->table('download__category')
            ->select(['id', 'refid', 'rus_name'])
            ->orderBy('refid')
            ->orderBy('id')
            ->get();

        foreach ($categories as $category) {
            $parentId = (int) $category->refid;
            $baseSlug = Str::slug((string) $category->rus_name);
            if ($baseSlug === '') {
                $baseSlug = 'section-' . $category->id;
            }

            if (in_array($baseSlug, self::RESERVED_CATEGORY_SLUGS, true)) {
                $baseSlug .= '-section';
            }

            $slug = $baseSlug;
            $suffix = 2;

            while (
                $connection->table('download__category')
                    ->where('refid', $parentId)
                    ->where('slug', $slug)
                    ->where('id', '!=', $category->id)
                    ->exists()
            ) {
                $slug = $baseSlug . '-' . $suffix;
                ++$suffix;
            }

            $connection->table('download__category')
                ->where('id', $category->id)
                ->update(['slug' => $slug]);
        }

        try {
            $connection->statement('ALTER TABLE `download__category` ADD UNIQUE `download__category_refid_slug_unique` (`refid`, `slug`)');
        } catch (Throwable) {
        }

        $io->writeln('download__category: slugs generated.');

        // --- download__files ---

        $schema->table('download__files', static function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('download__files', 'slug')) {
                $table->string('slug')->nullable()->after('rus_name');
            }
        });

        $files = $connection->table('download__files')
            ->select(['id', 'refid', 'rus_name'])
            ->orderBy('refid')
            ->orderBy('id')
            ->get();

        foreach ($files as $file) {
            $categoryId = (int) $file->refid;
            $baseSlug = Str::slug((string) $file->rus_name);
            if ($baseSlug === '') {
                $baseSlug = 'file';
            }

            $slug = $baseSlug;
            $suffix = 2;

            while (
                $connection->table('download__files')
                    ->where('refid', $categoryId)
                    ->where('slug', $slug)
                    ->where('id', '!=', $file->id)
                    ->exists()
            ) {
                $slug = $baseSlug . '-' . $suffix;
                ++$suffix;
            }

            $connection->table('download__files')
                ->where('id', $file->id)
                ->update(['slug' => $slug]);
        }

        try {
            $connection->statement('ALTER TABLE `download__files` ADD UNIQUE `download__files_refid_slug_unique` (`refid`, `slug`)');
        } catch (Throwable) {
        }

        $io->writeln('download__files: slugs generated.');

        $this->tracker->markCompleted($this->getName());

        $io->success('The update was completed successfully.');

        return self::SUCCESS;
    }
}
