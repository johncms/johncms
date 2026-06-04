<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class UpdateProfileCommand
{
    public function __construct(
        public string $imname,
        public string $live,
        public string $dayb,
        public string $monthb,
        public string $yearofbirth,
        public string $about,
        public string $mibile,
        public string $mail,
        public int $mailvis,
        public string $skype,
        public string $jabber,
        public string $www,
        public string $status,
        public string $csrfToken,
        // Admin-only fields (applied only when the editor has rights >= 7)
        public string $name,
        public int $karmaOff,
        public string $sex,
        public int $rights,
        public string $adminNotes,
    ) {
    }

    /**
     * The editable profile attributes (without the CSRF token).
     *
     * @return array<string, mixed>
     */
    public function toFormData(): array
    {
        return [
            'imname'      => $this->imname,
            'live'        => $this->live,
            'dayb'        => $this->dayb,
            'monthb'      => $this->monthb,
            'yearofbirth' => $this->yearofbirth,
            'about'       => $this->about,
            'mibile'      => $this->mibile,
            'mail'        => $this->mail,
            'mailvis'     => $this->mailvis,
            'skype'       => $this->skype,
            'jabber'      => $this->jabber,
            'www'         => $this->www,
            'status'      => $this->status,
            'name'        => $this->name,
            'karma_off'   => $this->karmaOff,
            'sex'         => $this->sex,
            'rights'      => $this->rights,
            'admin_notes' => $this->adminNotes,
        ];
    }
}
