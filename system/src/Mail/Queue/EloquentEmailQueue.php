<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Mail\Queue;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Johncms\Mail\EmailMessage;

/**
 * The mail queue on top of the `email_messages` table.
 *
 * A batch is taken by claiming it first: one UPDATE stamps the rows with the identifier of this
 * worker, and only then are they read back. Two workers running at once — the cron of a busy site
 * firing again before the previous run finished — therefore cannot pick the same message, without
 * needing row locks the older MySQL versions do not offer.
 */
final readonly class EloquentEmailQueue implements EmailQueueInterface
{
    /** Longer errors say nothing more, and the column is read by people, not by code. */
    private const ERROR_LENGTH_LIMIT = 1000;

    public function __construct(private MailQueueSettings $settings)
    {
    }

    public function claim(int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        $now = Carbon::now();
        $worker = $this->workerId();

        // Pending: neither sent nor given up on.
        $claimed = EmailMessage::query()
            ->whereNull('sent_at')
            ->whereNull('failed_at')
            ->where(
                static function (Builder $query) use ($now): void {
                    $query->whereNull('available_at')->orWhere('available_at', '<=', $now);
                }
            )
            ->where(
                // A claim nobody released within the timeout belonged to a process that died
                // mid-send; the message goes back into circulation rather than being stuck.
                function (Builder $query) use ($now): void {
                    $query->whereNull('locked_at')
                        ->orWhere('locked_at', '<', $now->copy()->subSeconds($this->settings->lockTimeout));
                }
            )
            ->orderBy('priority')
            ->orderBy('id')
            ->limit($limit)
            ->update(['locked_at' => $now, 'locked_by' => $worker]);

        if ($claimed === 0) {
            return [];
        }

        /** @var list<EmailMessage> $messages */
        $messages = EmailMessage::query()
            ->whereNull('sent_at')
            ->whereNull('failed_at')
            ->where('locked_by', $worker)
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->all();

        return $messages;
    }

    public function markSent(EmailMessage $message): void
    {
        $this->update(
            $message,
            [
                'sent_at'    => Carbon::now(),
                'last_error' => null,
                'locked_at'  => null,
                'locked_by'  => null,
            ]
        );
    }

    public function markAttemptFailed(EmailMessage $message, string $error): bool
    {
        $attempts = $message->attempts + 1;

        if ($attempts >= $this->settings->maxAttempts) {
            $this->giveUp($message, $error, $attempts);

            return false;
        }

        $this->update(
            $message,
            [
                'attempts'     => $attempts,
                'last_error'   => $this->trimError($error),
                'available_at' => Carbon::now()->addSeconds($this->settings->retryDelay($attempts)),
                'locked_at'    => null,
                'locked_by'    => null,
            ]
        );

        return true;
    }

    public function markFailed(EmailMessage $message, string $error): void
    {
        $this->giveUp($message, $error, $message->attempts + 1);
    }

    private function giveUp(EmailMessage $message, string $error, int $attempts): void
    {
        $this->update(
            $message,
            [
                'attempts'     => $attempts,
                'last_error'   => $this->trimError($error),
                'failed_at'    => Carbon::now(),
                'available_at' => null,
                'locked_at'    => null,
                'locked_by'    => null,
            ]
        );
    }

    public function pruneSent(int $days): int
    {
        if ($days < 1) {
            return 0;
        }

        return EmailMessage::query()
            ->whereNotNull('sent_at')
            ->where('sent_at', '<', Carbon::now()->subDays($days))
            ->delete();
    }

    /**
     * @param array<string, mixed> $values
     */
    private function update(EmailMessage $message, array $values): void
    {
        EmailMessage::query()->whereKey($message->getKey())->update($values);
    }

    private function trimError(string $error): string
    {
        return mb_substr(trim($error), 0, self::ERROR_LENGTH_LIMIT);
    }

    /**
     * Identifies one batch of one process, so that a worker reads back exactly the rows it just
     * claimed even when another worker is claiming at the same moment.
     */
    private function workerId(): string
    {
        return sprintf('%d-%s', getmypid(), bin2hex(random_bytes(8)));
    }
}
