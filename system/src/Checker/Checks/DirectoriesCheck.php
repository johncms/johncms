<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\FilesystemProbeInterface;
use Johncms\Checker\SystemCheckInterface;
use Johncms\Checker\WorkingDirectories;

final readonly class DirectoriesCheck implements SystemCheckInterface
{
    public function __construct(private WorkingDirectories $directories, private FilesystemProbeInterface $filesystem)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO('filesystem.directories', d__('system', 'Working directories'), d__('system', 'Filesystem'), 'database', order: 40);
    }

    public function run(): array
    {
        $results = [];
        foreach ($this->directories->all() as $id => $path) {
            $writable = $this->filesystem->canWriteDirectory($path);
            $results[] = new CheckResultDTO(
                'filesystem.directory.' . $id,
                $path === '' ? $id : $path,
                $writable ? d__('system', 'Writable') : d__('system', 'Not writable'),
                $writable ? CheckStatus::Passed : CheckStatus::Failed,
                CheckSeverity::Critical,
                d__('system', 'The PHP process must be able to write here or create this directory. Check its owner and permissions; granting access to everyone is not required.'),
            );
        }

        return $results;
    }
}
