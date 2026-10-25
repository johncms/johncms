<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Modules\News\Domain\Repository\NewsConfigRepositoryInterface;

/**
 * The settings of the news held in memory: what a use case wrote, without news.local.php being
 * touched.
 */
final class FakeNewsConfigRepository implements NewsConfigRepositoryInterface
{
    /** @var array<string, mixed>|null Null until something is saved. */
    public ?array $saved = null;

    public function getSettings(): array
    {
        return [];
    }

    public function saveSettings(array $settings): void
    {
        $this->saved = $settings;
    }
}
