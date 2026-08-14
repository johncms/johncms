<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\SecureToken;
use Johncms\Mail\EmailMessage;
use Johncms\Modules\Profile\Application\DTO\UpdateProfileCommand;
use Johncms\Modules\Profile\Application\Exceptions\EditProfileException;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\System\i18n\Translator;
use Johncms\Users\User;
use Johncms\Validator\Rules\Between;
use Johncms\Validator\Rules\EmailAddress;
use Johncms\Validator\Rules\InArray;
use Johncms\Validator\Rules\ModelNotExists;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;

final readonly class UpdateProfileUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private Translator $translator,
        private User $currentUser,
        private ValidatorInterface $validator,
    ) {
    }

    public function execute(UpdateProfileCommand $command, User $profileUser): void
    {
        $config = config('johncms');
        $isAdmin = $this->currentUser->rights >= 7;

        $formData = $command->toFormData();

        // Everything a visitor may leave blank says so explicitly: under the new null policy a
        // field carrying a rule is required unless the rule allows an empty value.
        $validationRules = [
            'imname'      => [new StringLength(max: 100)],
            'live'        => [new StringLength(max: 100, allowEmpty: true)],
            'dayb'        => [new Between(min: 1, max: 31)],
            'monthb'      => [new Between(min: 1, max: 12)],
            'yearofbirth' => [new Between(min: 1900, max: 3000)],
            'mibile'      => [new StringLength(max: 50, allowEmpty: true)],
            'skype'       => [new StringLength(max: 50, allowEmpty: true)],
            'jabber'      => [new StringLength(max: 50, allowEmpty: true)],
            'www'         => [new StringLength(max: 50, allowEmpty: true)],
            'mail'        => [
                new ModelNotExists(
                    model: User::class,
                    field: 'mail',
                    // Somebody else's address is taken; an empty one belongs to nobody.
                    exclude: static function ($query) use ($profileUser) {
                        return $query->where('mail', '!=', '')->where('id', '!=', $profileUser->id);
                    },
                    allowEmpty: true,
                ),
            ],
            'sex'         => [new InArray(haystack: ['m', 'zh'])],
        ];

        // When email confirmation is enabled, the address becomes mandatory and its host has to
        // resolve — the DNS check Symfony has no equivalent for.
        if (! empty($config['user_email_confirmation'])) {
            $validationRules['mail'][] = new EmailAddress(checkMxRecord: true);
        }

        // Clamp the requested rights to what the editor is allowed to assign
        if ($formData['rights'] > $this->currentUser->rights || $formData['rights'] > 9 || $formData['rights'] < 0) {
            $formData['rights'] = 0;
        }

        if ($isAdmin) {
            $validationRules['name'] = [new StringLength(min: 2, max: 25)];
        }

        $this->validator->validate($formData, $validationRules)->throwIfInvalid(
            static fn (array $errors): EditProfileException => new EditProfileException($errors)
        );

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
            $confirmationCode = SecureToken::generate();

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
