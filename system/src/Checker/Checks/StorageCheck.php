<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckProfile;
use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\SystemCheckInterface;
use Johncms\Storage\StorageRegistryInterface;
use Johncms\Storage\StorageSettings;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class StorageCheck implements SystemCheckInterface
{
    public function __construct(private StorageSettings $settings, private StorageRegistryInterface $storage, private LoggerInterface $logger)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO('storage.round_trip', d__('system', 'Storage read and write'), d__('system', 'Cache and storage'), 'database', [CheckProfile::Extended], 71);
    }

    public function run(): array
    {
        // Include a missing default disk so that an invalid configuration is visible as well.
        $names = array_unique([$this->settings->default, ...array_keys($this->settings->disks)]);
        $results = [];
        foreach ($names as $name) {
            try {
                $disk = $this->storage->disk($name);
                $path = 'johncms-system-check-' . bin2hex(random_bytes(16)) . '.txt';
                $contents = bin2hex(random_bytes(16));
                try {
                    $disk->store($path, $contents);
                    $matches = $disk->read($path) === $contents;
                } finally {
                    $disk->delete($path);
                }
                $status = $matches ? CheckStatus::Passed : CheckStatus::Failed;
            } catch (Throwable $exception) {
                $this->logger->error('Storage system check failed.', ['disk' => $name, 'exception' => $exception]);
                $status = CheckStatus::Unavailable;
            }
            $results[] = new CheckResultDTO(
                'storage.round_trip.' . $name,
                d__('system', 'Storage: %s', $name),
                match ($status) {
                    CheckStatus::Passed => d__('system', 'Yes'),
                    CheckStatus::Failed => d__('system', 'No'),
                    default => d__('system', 'Could not check'),
                },
                $status,
                CheckSeverity::Critical,
                $status === CheckStatus::Unavailable
                    ? d__('system', 'The storage test could not finish. Check its configuration and application log, including any failure to remove the temporary test file.')
                    : d__('system', 'A temporary file was written, read back and removed. The contents must match what was written.'),
            );
        }

        return $results;
    }
}
