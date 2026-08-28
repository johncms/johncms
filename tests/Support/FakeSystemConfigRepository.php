<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;

/**
 * The configuration of the site held in memory: what a settings use case wrote, without
 * system.local.php being touched.
 */
final class FakeSystemConfigRepository implements SystemConfigRepositoryInterface
{
    /** @var array<string, mixed> */
    public array $saved = [];

    /**
     * @param array<string, mixed> $existing What the site is configured with before the save.
     */
    public function __construct(private readonly array $existing = [])
    {
    }

    public function getJohncms(): array
    {
        return $this->existing;
    }

    public function saveJohncms(array $johncms): void
    {
        $this->saved = $johncms;
    }
}
