<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\SystemChecks;

use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\FilesystemProbeInterface;
use Johncms\Checker\SystemCheckInterface;
use Johncms\Checker\WorkingDirectories;
use Johncms\Modules\Admin\Domain\Services\ServerHealth;

final readonly class DiskSpaceCheck implements SystemCheckInterface
{
    public function __construct(private WorkingDirectories $directories, private FilesystemProbeInterface $filesystem, private ServerHealth $health)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO('filesystem.space', d__('admin', 'Free disk space'), d__('system', 'Filesystem'), 'database', order: 41);
    }

    public function run(): array
    {
        $results = [];
        foreach (array_unique($this->directories->all()) as $id => $path) {
            $disk = $this->filesystem->diskSpace($path);
            $results[] = new CheckResultDTO(
                'filesystem.space.' . $id,
                d__('admin', 'Free space: %s', $path),
                $disk === null ? d__('system', 'Could not check') : sprintf('%.1f%% (%.1f MiB)', 100 * $disk['free'] / $disk['total'], $disk['free'] / 1048576),
                $disk === null ? CheckStatus::Unavailable : ($this->health->diskLow($disk) ? CheckStatus::Failed : CheckStatus::Passed),
                CheckSeverity::Warning,
                d__('admin', 'Keep at least %d%% of the filesystem free for uploads, cache and logs.', (int) (ServerHealth::DISK_LOW_SHARE * 100)),
            );
        }

        return $results;
    }
}
