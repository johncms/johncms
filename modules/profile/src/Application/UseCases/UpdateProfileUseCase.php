<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Mail\EmailMessage;
use Johncms\Modules\Profile\Application\DTO\UpdateProfileCommand;
use Johncms\Modules\Profile\Application\Exceptions\EditProfileException;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\System\i18n\Translator;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Laminas\Validator\Hostname;

final readonly class UpdateProfileUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private Translator $translator,
        private User $currentUser,
    ) {
    }

    public function execute(UpdateProfileCommand $command, User $profileUser): void
    {
        $config = config('johncms');
        $isAdmin = $this->currentUser->rights >= 7;

        $formData = $command->toFormData();

        $validationRules = [
            'imname'      => [
                'NotEmpty',
                'StringLength' => ['max' => 100],
            ],
            'live'        => ['StringLength' => ['max' => 100]],
            'dayb'        => ['Between' => ['min' => 1, 'max' => 31]],
            'monthb'      => ['Between' => ['min' => 1, 'max' => 12]],
            'yearofbirth' => ['Between' => ['min' => 1900, 'max' => 3000]],
            'mibile'      => ['StringLength' => ['max' => 50]],
            'skype'       => ['StringLength' => ['max' => 50]],
            'jabber'      => ['StringLength' => ['max' => 50]],
            'www'         => ['StringLength' => ['max' => 50]],
            'mail'        => [
                'ModelNotExists' => [
                    'model'   => User::class,
                    'field'   => 'mail',
                    'exclude' => static function ($query) use ($profileUser) {
                        return $query->where('mail', '!=', '')->where('id', '!=', $profileUser->id);
                    },
                ],
            ],
            'sex'         => ['InArray' => ['haystack' => ['m', 'zh']]],
        ];

        // When email confirmation is enabled, the email becomes mandatory and is validated against DNS
        if (! empty($config['user_email_confirmation'])) {
            $validationRules['mail'][] = 'NotEmpty';
            $validationRules['mail']['EmailAddress'] = [
                'allow'          => Hostname::ALLOW_DNS,
                'useMxCheck'     => true,
                'useDeepMxCheck' => true,
            ];
        }

        // Clamp the requested rights to what the editor is allowed to assign
        if ($formData['rights'] > $this->currentUser->rights || $formData['rights'] > 9 || $formData['rights'] < 0) {
            $formData['rights'] = 0;
        }

        if ($isAdmin) {
            $validationRules['name'] = ['StringLength' => ['min' => 2, 'max' => 25]];
        }

        $validator = new Validator($formData, $validationRules);
        if (! $validator->isValid()) {
            throw new EditProfileException($validator->getErrors());
        }

        // Regular users cannot change administrative fields
        if (! $isAdmin) {
            unset($formData['name'], $formData['karma_off'], $formData['sex'], $formData['rights'], $formData['admin_notes']);
        }

        // Admins cannot change their own rights: the form does not render this field for self-edit,
        // so keeping it would silently reset the editor's own rights to the default value.
        if ($profileUser->id === $this->currentUser->id) {
            unset($formData['rights']);
        }

        // For regular users changing their email, defer the change until it is confirmed by email
        if (! empty($config['user_email_confirmation']) && $profileUser->mail !== $formData['mail'] && ! $isAdmin) {
            $newEmail = $formData['mail'];
            $confirmationCode = uniqid('email_', true);

            $formData['new_email'] = $newEmail;
            $formData['mail'] = $profileUser->mail;
            $formData['confirmation_code'] = $confirmationCode;

            $this->sendEmailChangeMessages($profileUser, $newEmail, $confirmationCode, $config['homeurl'] ?? '');
        }

        $this->profileUserRepository->updateProfile($profileUser->id, $formData);
    }

    private function sendEmailChangeMessages(User $profileUser, string $newEmail, string $confirmationCode, string $homeUrl): void
    {
        $link = $homeUrl . '/profile/confirm-email/' . $profileUser->id . '/' . $confirmationCode;

        (new EmailMessage())->create(
            [
                'priority' => 1,
                'locale'   => $this->translator->getLocale(),
                'template' => '@theme/emails/confirm-email-change.twig',
                'fields'   => [
                    'email_to'        => $newEmail,
                    'name_to'         => $profileUser->name,
                    'subject'         => __('Confirm email change'),
                    'user_name'       => $profileUser->name,
                    'link_to_confirm' => $link,
                ],
            ]
        );

        // Notify the current address that an email change procedure has started
        if (! empty($profileUser->mail)) {
            (new EmailMessage())->create(
                [
                    'priority' => 1,
                    'locale'   => $this->translator->getLocale(),
                    'template' => '@theme/emails/changed-email-notification.twig',
                    'fields'   => [
                        'email_to'  => $profileUser->mail,
                        'name_to'   => $profileUser->name,
                        'subject'   => __('The procedure for changing the email address was started'),
                        'user_name' => $profileUser->name,
                        'new_email' => $newEmail,
                    ],
                ]
            );
        }
    }
}
