<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Config;

use Johncms\Modules\Admin\Domain\Repository\CaptchaConfigRepositoryInterface;

final readonly class FileCaptchaConfigRepository implements CaptchaConfigRepositoryInterface
{
    private const FILE = 'captcha.local.php';

    private const SECTION = 'captcha';

    public function __construct(private LocalConfigWriter $writer)
    {
    }

    public function get(): array
    {
        return (array) config(self::SECTION, []);
    }

    public function save(array $captcha): void
    {
        // The keys of the services land in the gitignored half of the configuration, next to the
        // database password — never in captcha.global.php, which is in the repository.
        $this->writer->write(self::FILE, self::SECTION, $captcha);
    }
}
