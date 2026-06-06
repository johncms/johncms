<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\Exceptions\AlbumAccessDeniedException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPasswordRequiredException;
use Johncms\Modules\Album\Domain\Enums\AlbumAccess;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Users\User;

/**
 * Shared access guard for an album (reused by show, comments and download).
 *
 * Mirrors the legacy show.php flow: private albums are owner/admin only,
 * password-protected albums keep the unlocked password in the `ap` session key.
 */
final readonly class EnsureAlbumAccessUseCase
{
    private const ADMIN_RIGHTS = 7;

    public function __construct(
        private User $currentUser,
    ) {
    }

    /**
     * @throws AlbumAccessDeniedException     when a private album is closed for the viewer
     * @throws AlbumPasswordRequiredException when a password-protected album is still locked
     */
    public function execute(Album $album, ?string $submittedPassword = null): void
    {
        $access = AlbumAccess::fromStored($album->access);

        // Leaving a non password-protected album clears any unlocked-password session.
        if ($access !== AlbumAccess::Password) {
            unset($_SESSION['ap']);
        }

        if ($album->user_id === $this->currentUser->id || $this->currentUser->rights >= self::ADMIN_RIGHTS) {
            return;
        }

        if ($access === AlbumAccess::Private) {
            throw new AlbumAccessDeniedException($album->user_id);
        }

        if ($access === AlbumAccess::Password) {
            $incorrectPassword = false;
            if ($submittedPassword !== null) {
                if ($album->password === trim($submittedPassword)) {
                    $_SESSION['ap'] = $album->password;
                } else {
                    $incorrectPassword = true;
                }
            }

            if (! isset($_SESSION['ap']) || $_SESSION['ap'] !== $album->password) {
                throw new AlbumPasswordRequiredException($album->id, $album->user_id, $incorrectPassword);
            }
        }
    }
}
