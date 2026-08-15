<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * One role in the list of what an account may be given.
 */
final readonly class UserRoleRowDTO
{
    /**
     * @param string|null $expiresAt  The date the grant runs out, as the form field carries it
     *                                (YYYY-MM-DD); null when it never does.
     * @param bool        $expired    The grant is still on record but has already run out.
     * @param bool        $manageable A role above the visitor's own level is listed, but they may
     *                                neither grant nor take it away.
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public int $level,
        public bool $granted,
        public ?string $expiresAt,
        public bool $expired,
        public bool $manageable,
    ) {
    }
}
