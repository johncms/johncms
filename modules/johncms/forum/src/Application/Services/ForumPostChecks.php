<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Security\AntifloodCheckerInterface;

/**
 * What a new post or an answer has to pass before it is written: the antiflood, and a text that
 * is there, long enough and not the writer's last post sent again.
 */
final readonly class ForumPostChecks
{
    private const int MIN_LENGTH = 4;

    public function __construct(
        private AntifloodCheckerInterface $antiflood,
        private ForumMessageRepositoryInterface $messages,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * Messages by field of the form: message for the text, _form for the antiflood; empty when the
     * post may be written.
     *
     * @return array<string, list<string>>
     */
    public function errors(string $text): array
    {
        $flood = $this->antiflood->getRemainingSeconds();
        if ($flood > 0) {
            return ['_form' => [sprintf(d__('system', 'You cannot write so often. Wait %d seconds.'), $flood)]];
        }

        if ($text === '') {
            return ['message' => [__('You have not entered the message')]];
        }

        if (mb_strlen($text) < self::MIN_LENGTH) {
            return ['message' => [__('Text is too short')]];
        }

        $last = $this->messages->findLastMessageByUser($this->currentUser->id());
        if ($last !== null && $text === (string) $last->getRawOriginal('text')) {
            return ['message' => [__('Message already exists')]];
        }

        return [];
    }
}
