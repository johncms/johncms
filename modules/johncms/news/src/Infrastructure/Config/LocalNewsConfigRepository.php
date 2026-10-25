<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Infrastructure\Config;

use Johncms\Modules\Admin\Infrastructure\Config\LocalConfigWriter;
use Johncms\Modules\News\Domain\Repository\NewsConfigRepositoryInterface;

/**
 * The settings of the news in config/autoload/news.local.php, over the defaults of news.global.php.
 */
final readonly class LocalNewsConfigRepository implements NewsConfigRepositoryInterface
{
    public function __construct(
        private LocalConfigWriter $writer,
    ) {
    }

    public function getSettings(): array
    {
        return (array) (config('news') ?? []);
    }

    public function saveSettings(array $settings): void
    {
        $this->writer->write('news.local.php', 'news', $settings);
    }
}
