<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Identity;

/**
 * An access checker that allows the permissions the test names and refuses everything else.
 */
final readonly class FakeAccessChecker implements AccessCheckerInterface
{
    /**
     * @param list<string> $granted
     */
    public function __construct(private array $granted = [])
    {
    }

    public function allows(string $permission, mixed $subject = null): bool
    {
        return in_array($permission, $this->granted, true);
    }

    public function allowsFor(Identity $identity, string $permission, mixed $subject = null): bool
    {
        return $this->allows($permission, $subject);
    }
}
