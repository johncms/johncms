<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Console;

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

#[AsCommand(
    name: 'library:generate-slugs',
    description: 'One-time: add slug columns and generate slugs for library categories and articles',
)]
final class GenerateLibrarySlugsCommand extends Command
{
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

        // --- Categories ---

        if (! $schema->hasColumn('library_cats', 'slug')) {
            $schema->table('library_cats', static function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });
            $io->writeln('Added slug column to library_cats');
        }

        if (! $schema->hasIndex('library_cats', 'library_cats_parent_slug_unique')) {
            $schema->table('library_cats', static function (Blueprint $table) {
                $table->unique(['parent', 'slug'], 'library_cats_parent_slug_unique');
            });
            $io->writeln('Added unique index on library_cats(parent, slug)');
        }

        Capsule::table('library_cats')->update(['slug' => null]);

        $categories = Capsule::table('library_cats')->orderBy('id')->get(['id', 'parent', 'name']);
        $usedCatSlugs = [];
        $catUpdated = 0;

        foreach ($categories as $category) {
            $baseSlug = Str::slug($category->name);
            if ($baseSlug === '') {
                $baseSlug = 'section';
            }

            $slug = $baseSlug;
            $suffix = 2;
            while (isset($usedCatSlugs[$category->parent . ':' . $slug])) {
                $slug = $baseSlug . '-' . $suffix;
                ++$suffix;
            }

            $usedCatSlugs[$category->parent . ':' . $slug] = true;
            Capsule::table('library_cats')->where('id', $category->id)->update(['slug' => $slug]);
            ++$catUpdated;
        }

        $io->writeln("Categories: updated $catUpdated slugs");

        // --- Articles ---

        if (! $schema->hasColumn('library_texts', 'slug')) {
            $schema->table('library_texts', static function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });
            $io->writeln('Added slug column to library_texts');
        }

        Capsule::table('library_texts')->update(['slug' => null]);

        $articles = Capsule::table('library_texts')->orderBy('id')->get(['id', 'cat_id', 'name']);
        $usedArticleSlugs = [];
        $articleUpdated = 0;

        foreach ($articles as $article) {
            $baseSlug = Str::slug($article->name);
            if ($baseSlug === '') {
                $baseSlug = 'article';
            }

            $slug = $baseSlug;
            $suffix = 2;
            while (isset($usedArticleSlugs[$article->cat_id . ':' . $slug])) {
                $slug = $baseSlug . '-' . $suffix;
                ++$suffix;
            }

            $usedArticleSlugs[$article->cat_id . ':' . $slug] = true;
            Capsule::table('library_texts')->where('id', $article->id)->update(['slug' => $slug]);
            ++$articleUpdated;
        }

        $io->writeln("Articles: updated $articleUpdated slugs");

        $this->tracker->markCompleted($this->getName());

        $io->success('Done.');

        return self::SUCCESS;
    }
}
