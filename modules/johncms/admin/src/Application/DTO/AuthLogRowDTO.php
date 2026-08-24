<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * One line of the sign-in log, ready for the template.
 */
final readonly class AuthLogRowDTO
{
    /**
     * @param string      $event     The key as it is stored, kept so an entry from a module the
     *                               core knows nothing about is still shown.
     * @param string      $label     The translated name of the event, or the key when it is not
     *                               one of ours.
     * @param string|null $userName  Null when the entry names nobody — a sign-in attempt on a
     *                               login that does not exist.
     * @param string|null $actorName Who did it, when that is somebody other than the account.
     * @param list<string> $details  The context, flattened into "key: value" lines.
     */
    public function __construct(
        public int $id,
        public int $createdAt,
        public string $event,
        public string $label,
        public ?int $userId,
        public ?string $userName,
        public ?int $actorId,
        public ?string $actorName,
        public string $ip,
        public string $userAgent,
        public array $details,
    ) {
    }
}
