<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Registration\Forms;

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
        return [
            'login'    => (new InputText())
                ->setLabel(__('Login'))
                ->setPlaceholder(__('Enter your login'))
                ->setNameAndId('login')
                ->setHelpText(__('Min. %s, Max. %s characters. Allowed letters of the latin alphabets and numbers.', 3, 50))
                ->setValue($this->getValue('login'))
                ->setValidationRules(
                    [
                        'NotEmpty',
                        'StringLength'   => ['min' => 3, 'max' => 50],
                        'ModelNotExists' => [
                            'model' => User::class,
                            'field' => 'login',
                        ],
                    ]
                ),
            'email'    => (new InputText())
                ->setLabel(__('E-mail'))
                ->setPlaceholder(__('Enter your e-mail'))
                ->setNameAndId('email')
                ->setHelpText(__('Specify an existing e-mail because a confirmation of registration will be sent to it.'))
                ->setValue($this->getValue('email'))
                ->setValidationRules(
                    [
                        'NotEmpty',
                        'ModelNotExists' => [
                            'model' => User::class,
                            'field' => 'email',
                        ],
                        'EmailAddress'   => [
                            'allow'          => Hostname::ALLOW_DNS,
                            'useMxCheck'     => true,
                            'useDeepMxCheck' => true,
                        ],
                    ]
                ),
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
    }
}
