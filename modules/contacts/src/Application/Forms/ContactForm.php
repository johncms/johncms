<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\Forms;

use Johncms\Modules\Contacts\Domain\Models\ContactMessage;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Users\User;
use Laminas\Validator\Hostname;

final readonly class ContactForm
{
    /**
     * Hidden field that must stay empty: bots fill it in, humans never see it.
     */
    public const HONEYPOT_FIELD = 'contact_link';

    public function __construct(
        private Request $request,
        private Environment $environment,
        private User $user,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getFormData(): array
    {
        $formData = [
            'name'                 => $this->request->body('name', ''),
            'email'                => $this->request->body('email', ''),
            'message'              => $this->request->body('message', ''),
            'code'                 => $this->request->body('code', ''),
            'csrf_token'           => $this->request->body('csrf_token', ''),
            self::HONEYPOT_FIELD   => $this->request->body(self::HONEYPOT_FIELD, ''),
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
     * @return array<string, array<int|string, mixed>>
     * @psalm-suppress MissingClosureReturnType,MissingClosureParamType
     */
    public function getValidationRules(): array
    {
        $rules = [
            'name'               => [
                'NotEmpty',
                'StringLength' => ['min' => 2, 'max' => 50],
            ],
            'email'              => [
                'NotEmpty',
                'EmailAddress' => ['allow' => Hostname::ALLOW_DNS],
            ],
            'message'            => [
                'NotEmpty',
                'StringLength'   => ['min' => 10, 'max' => 5000],
                'ModelNotExists' => [
                    'model'   => ContactMessage::class,
                    'field'   => 'message',
                    'exclude' => function ($query) {
                        $query->where('ip_address', (string) $this->environment->getIp(false))
                            ->where('created_at', '>', date('Y-m-d H:i:s', time() - 600));
                    },
                ],
            ],
            // Honeypot: any value here means the form was submitted by a bot.
            self::HONEYPOT_FIELD => [
                'Identical' => ['token' => ''],
            ],
            'csrf_token'         => [
                'Csrf',
                'Flood',
                'Ban' => [
                    'bans' => [1, 13],
                ],
            ],
        ];

        if (! $this->user->isValid()) {
            $rules['code'] = ['Captcha'];
        }

        return $rules;
    }
}
