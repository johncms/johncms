<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Twig\Extension\RuntimeExtensionInterface;

final readonly class AuthRuntime implements RuntimeExtensionInterface
{
    public function __construct(private AccessCheckerInterface $accessChecker)
    {
    }

    public function can(string $permission, mixed $subject = null): bool
    {
        return $this->accessChecker->allows($permission, $subject);
    }
}
