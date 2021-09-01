<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Auth\Forms;

use Johncms\Forms\AbstractForm;
use Johncms\Forms\Inputs\Captcha;
use Johncms\Forms\Inputs\InputPassword;
use Johncms\Forms\Inputs\InputText;
use Johncms\Users\User;
use Laminas\Validator\Hostname;

class RegistrationForm extends AbstractForm
{
    protected function prepareFormFields(): array
    {
        $fields = [];
        $fields['login'] = (new InputText())
            ->setLabel(__('Login'))
            ->setPlaceholder(__('Enter your login'))
            ->setNameAndId('login')
            ->setHelpText(__('Min. %s, Max. %s characters. Allowed letters of the latin alphabets and numbers.', 3, 50))
            ->setValue($this->getValue('login'))
            ->setValidationRules(
                [
                    'NotEmpty',
                    'Regex'          => ['pattern' => '/^[A-Za-z0-9_]+$/'],
                    'StringLength'   => ['min' => 3, 'max' => 50],
                    'ModelNotExists' => [
                        'model' => User::class,
                        'field' => 'login',
                    ],
                ]
            );

        if (config('registration.show_email', true)) {
            $emailValidator = [
                'ModelNotExists' => [
                    'model' => User::class,
                    'field' => 'email',
                ],
            ];

            if (config('registration.email_required', true)) {
                $emailValidator[] = 'NotEmpty';
                $emailValidator['EmailAddress'] = [
                    'allow'          => Hostname::ALLOW_DNS,
                    'useMxCheck'     => true,
                    'useDeepMxCheck' => true,
                ];
            }

            $confirmation = config('registration.email_confirmation', false);

            $fields['email'] = (new InputText())
                ->setLabel(__('E-mail'))
                ->setPlaceholder(__('Enter your e-mail'))
                ->setNameAndId('email')
                ->setHelpText($confirmation ? __('Specify an existing e-mail because a confirmation of registration will be sent to it.') : '')
                ->setValue($this->getValue('email'))
                ->setValidationRules($emailValidator);
        }

        $fields += [
            'password' => (new InputPassword())
                ->setLabel(__('Password'))
                ->setPlaceholder(__('Password'))
                ->setNameAndId('password')
                ->setHelpText(__('Min. %s characters.', 6))
                ->setValidationRules(
                    [
                        'NotEmpty',
                        'StringLength' => ['min' => 6],
                    ]
                ),
            'captcha'  => (new Captcha())
                ->setLabel(__('Enter verification code'))
                ->setPlaceholder(__('Verification code'))
                ->setNameAndId('captcha')
                ->setValidationRules(['Captcha']),
        ];

        return $fields;
    }
}
