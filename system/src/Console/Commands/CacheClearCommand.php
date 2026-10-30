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

        $items = @scandir($directory);
        if ($items === false) {
            if (! is_dir($directory)) {
                return [0, 0];
            }

            throw new RuntimeException(sprintf('Cannot read cache directory "%s": %s', $directory, $this->lastError()));
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.gitkeep') {
                continue;
            }

            $path = $this->path($directory, $item);

            if (is_link($path) || is_file($path)) {
                if (! @unlink($path)) {
                    $error = $this->lastError();

                    // The running site may prune the same entry while the command walks the tree.
                    if (! is_link($path) && ! file_exists($path)) {
                        continue;
                    }

                    throw new RuntimeException(sprintf('Failed to remove cache file "%s": %s', $path, $error));
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

            if (! @rmdir($path)) {
                $error = $this->lastError();

                // Requests of the running site write fresh entries while the command clears the
                // directory; those are new cache, not a failure, so the directory stays. A
                // directory that is already gone is not a failure either.
                if (! $this->isEmptyDirectory($path)) {
                    continue;
                }

                throw new RuntimeException(sprintf('Failed to remove cache directory "%s": %s', $path, $error));
            }

            ++$directoriesRemoved;
        }

        return [$filesRemoved, $directoriesRemoved];
    }

    private function isEmptyDirectory(string $directory): bool
    {
        $items = @scandir($directory);

        return $items !== false && array_diff($items, ['.', '..']) === [];
    }

    private function lastError(): string
    {
        return error_get_last()['message'] ?? 'unknown error';
    }

    private function path(string $directory, string $item): string
    {
        return rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $item;
    }
}
