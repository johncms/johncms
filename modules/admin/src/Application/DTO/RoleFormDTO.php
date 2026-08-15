<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * The role editor as it was submitted.
 */
final readonly class RoleFormDTO
{
    /**
     * @param int|null     $id          Null when the role is being created.
     * @param string       $slug        Ignored for a role that already exists: code refers to it.
     * @param list<string> $permissions The keys that were ticked.
     */
    public function __construct(
        public ?int $id,
        public string $slug,
        public string $name,
        public int $level,
        public array $permissions,
    ) {
    }
}
