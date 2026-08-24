<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Config;

use Johncms\Modules\Admin\Domain\Repository\AuthConfigRepositoryInterface;

final readonly class FileSystemAuthConfigRepository implements AuthConfigRepositoryInterface
{
    private const FILE = 'auth.local.php';

    private const SECTION = 'auth';

    public function __construct(private LocalConfigWriter $writer)
    {
    }

    public function get(): array
    {
        return config(self::SECTION) ?? [];
    }

    public function save(array $auth): void
    {
        $this->writer->write(self::FILE, self::SECTION, $auth);
    }
}
