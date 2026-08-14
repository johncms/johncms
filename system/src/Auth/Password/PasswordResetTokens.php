<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Password;

use Johncms\Auth\SecureToken;

/**
 * Issues and redeems the links sent by "forgot my password".
 *
 * Two steps rather than one, because the flow has two: opening the link only has to know that
 * it is still good (verify), while submitting the form is what spends it (consume). Checking on
 * the way in and spending on the way out is what keeps an opened-but-abandoned link usable and
 * a submitted one dead.
 */
final readonly class PasswordResetTokens
{
    /** How long a link works, in seconds. */
    public const TTL = 3600;

    /** How often one account may ask for a link, in seconds. */
    public const REQUEST_INTERVAL = 86400;

    public function __construct(
        private PasswordResetTokenRepositoryInterface $repository,
    ) {
    }

    /**
     * A fresh link for this account. Any request still outstanding is dropped, so only the
     * newest letter works — otherwise every letter ever sent would stay valid for its hour.
     *
     * @return string The secret to put in the link. It is not stored anywhere in this form and
     *                cannot be retrieved later.
     */
    public function issue(int $userId, ?int $now = null): string
    {
        $now ??= time();
        $token = SecureToken::generate();

        $this->repository->deleteForUser($userId);
        $this->repository->store($userId, SecureToken::hash($token), $now + self::TTL, $now);

        return $token;
    }

    /**
     * Whether the account may be sent another link, or has asked too recently.
     */
    public function canRequest(int $userId, ?int $now = null): bool
    {
        $last = $this->repository->lastRequestedAt($userId);

        return $last === null || $last <= ($now ?? time()) - self::REQUEST_INTERVAL;
    }

    /**
     * The account the link belongs to, without spending it.
     */
    public function verify(string $token, ?int $now = null): ?int
    {
        return $this->find($token, $now)?->user_id;
    }

    /**
     * The account the link belongs to, spending the link in the process: a second submission of
     * the same link changes nothing.
     */
    public function consume(string $token, ?int $now = null): ?int
    {
        $now ??= time();
        $record = $this->find($token, $now);

        if ($record === null) {
            return null;
        }

        $this->repository->markUsed($record->id, $now);

        return $record->user_id;
    }

    private function find(string $token, ?int $now): ?PasswordResetToken
    {
        if ($token === '') {
            return null;
        }

        return $this->repository->findUsable(SecureToken::hash($token), $now ?? time());
    }
}
