<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AsAdminTask;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'cache:clear',
    description: 'Clear application cache files',
)]
#[AsAdminTask(title: 'Clear cache', description: 'Remove all application cache files')]
final class CacheClearCommand extends Command
{
    private const PRESERVED_FILES = [
        'smilies-list.cache',
    ];

    public function __construct(
        private readonly string $cachePath = CACHE_PATH,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (! is_dir($this->cachePath)) {
            $io->warning(sprintf('Cache directory "%s" does not exist.', $this->cachePath));
            return self::SUCCESS;
        }

        try {
            [$filesRemoved, $directoriesRemoved] = $this->clearDirectory($this->cachePath);
        } catch (RuntimeException $exception) {
            $io->error($exception->getMessage());
            return self::FAILURE;
        }

        $io->success(sprintf(
            'Cache cleared. Removed %d files and %d directories.',
            $filesRemoved,
            $directoriesRemoved
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{int, int}
     */
    private function clearDirectory(string $directory): array
    {
        $filesRemoved = 0;
        $directoriesRemoved = 0;

        $items = scandir($directory);
        if ($items === false) {
            throw new RuntimeException(sprintf('Cannot read cache directory "%s".', $directory));
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.gitkeep' || in_array($item, self::PRESERVED_FILES, true)) {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_link($path) || is_file($path)) {
                if (! unlink($path)) {
                    throw new RuntimeException(sprintf('Failed to remove cache file "%s".', $path));
                }

                ++$filesRemoved;
                continue;
            }

            if (! is_dir($path)) {
                continue;
            }

            [$nestedFilesRemoved, $nestedDirectoriesRemoved] = $this->clearDirectory($path);
            $filesRemoved += $nestedFilesRemoved;
            $directoriesRemoved += $nestedDirectoriesRemoved;

            if (! rmdir($path)) {
                throw new RuntimeException(sprintf('Failed to remove cache directory "%s".', $path));
            }

            ++$directoriesRemoved;
        }

        return [$filesRemoved, $directoriesRemoved];
    }
}
