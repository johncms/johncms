<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Users\ProfileLinksInterface;

/**
 * Profile links for a visitor the test decides about: by default one who may open every profile.
 */
final readonly class FakeProfileLinks implements ProfileLinksInterface
{
    public function __construct(private bool $open = true)
    {
    }

    public function canView(int $userId): bool
    {
        return $this->open && $userId > 0;
    }

    public function urlOf(int $userId): ?string
    {
        return $this->canView($userId) ? '/profile/' . $userId : null;
    }
}
