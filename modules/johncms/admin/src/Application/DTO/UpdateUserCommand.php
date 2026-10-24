<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * The profile of an account as the form of the panel sends it, the text as it was typed.
 *
 * The fields of the staff — the nickname, the gender, the karma, the notes — are null when the
 * editor may not change them, so that nothing a hand-made request carries reaches the account.
 */
final readonly class UpdateUserCommand
{
    public function __construct(
        public string $mail,
        public bool $mailVisible,
        public string $fullName,
        public string $city,
        public int $birthDay,
        public int $birthMonth,
        public int $birthYear,
        public string $about,
        public string $phone,
        public string $skype,
        public string $jabber,
        public string $site,
        public string $status,
        public ?string $name = null,
        public ?string $sex = null,
        public ?bool $karmaOff = null,
        public ?string $notes = null,
    ) {
    }
}
