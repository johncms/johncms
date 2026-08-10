<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\Forms;

use Johncms\Modules\Contacts\Domain\Models\ContactMessage;
use Johncms\Http\Request;
use Johncms\Security\ClientInfoDTO;
use Johncms\Users\User;
use Johncms\Validator\Rules\Ban;
use Johncms\Validator\Rules\Captcha;
use Johncms\Validator\Rules\EmailAddress;
use Johncms\Validator\Rules\Flood;
use Johncms\Validator\Rules\Identical;
use Johncms\Validator\Rules\ModelNotExists;
use Johncms\Validator\Rules\RuleInterface;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidationResult;

final readonly class ContactForm
{
    /**
     * Hidden field that must stay empty: bots fill it in, humans never see it.
     */
    public const HONEYPOT_FIELD = 'contact_link';

    public function __construct(
        private User $user,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getFormData(Request $request): array
    {
        $formData = [
            'name'                 => $request->body('name', ''),
            'email'                => $request->body('email', ''),
            'message'              => $request->body('message', ''),
            'code'                 => $request->body('code', ''),
            self::HONEYPOT_FIELD   => $request->body(self::HONEYPOT_FIELD, ''),
        ];

        $formData = array_map('trim', $formData);

        if ($this->user->isValid()) {
            $formData['name'] = $this->user->name;
            if ($formData['email'] === '') {
                $formData['email'] = (string) $this->user->mail;
            }
        }

        return $formData;
    }

    /**
     * @return array<string, list<RuleInterface>>
     */
    public function getValidationRules(ClientInfoDTO $clientInfo): array
    {
        $rules = [
            'name'                     => [new StringLength(min: 2, max: 50)],
            'email'                    => [new EmailAddress()],
            'message'                  => [
                new StringLength(min: 10, max: 5000),
                new ModelNotExists(
                    model: ContactMessage::class,
                    field: 'message',
                    // The same message from the same address within ten minutes is a repeat
                    // submission; an identical one from elsewhere is not.
                    exclude: function ($query) use ($clientInfo): void {
                        $query->where('ip_address', $clientInfo->ip)
                            ->where('created_at', '>', date('Y-m-d H:i:s', time() - 600));
                    },
                ),
            ],
            // Honeypot: any value here means the form was submitted by a bot, so the field is
            // expected to stay empty and must not be required.
            self::HONEYPOT_FIELD       => [new Identical(token: '', allowEmpty: true)],
            ValidationResult::FORM_KEY => [
                new Flood(),
                new Ban(bans: [1, 13]),
            ],
        ];

        if (! $this->user->isValid()) {
            $rules['code'] = [new Captcha()];
        }

        return $rules;
    }
}
