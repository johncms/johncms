<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Admin\Forms;

use Johncms\Forms\AbstractForm;
use Johncms\Forms\Inputs\InputFile;
use Johncms\Forms\Inputs\InputPassword;
use Johncms\Forms\Inputs\InputText;
use Johncms\Forms\Inputs\Select;
use Johncms\Forms\Inputs\Textarea;
use Johncms\Users\Role;
use Johncms\Users\User;
use Laminas\Validator\Hostname;

class CreateUserForm extends AbstractForm
{
    protected function prepareFormFields(): array
    {
        $fields = [];

        $fields['login'] = (new InputText())
            ->setLabel(__('Login'))
            ->setPlaceholder(__('Enter login'))
            ->setNameAndId('login')
            ->setHelpText(__('Min. %s, Max. %s characters. Allowed letters of the latin alphabets and numbers.', 3, 150))
            ->setValue($this->getValue('login'))
            ->setValidationRules(
                [
                    'NotEmpty',
                    'Regex'          => ['pattern' => '/^[A-Za-z0-9_]+$/'],
                    'StringLength'   => ['min' => 3, 'max' => 150],
                    'ModelNotExists' => [
                        'model' => User::class,
                        'field' => 'login',
                    ],
                ]
            );

        $fields['password'] = (new InputPassword())
            ->setLabel(__('Password'))
            ->setPlaceholder(__('Password'))
            ->setNameAndId('password')
            ->setHelpText(__('Min. %s characters.', 6))
            ->setValidationRules(
                [
                    'NotEmpty',
                    'StringLength' => ['min' => 6],
                ]
            );

        $fields['roles'] = (new Select())
            ->setOptions($this->getRoles())
            ->setLabel(__('Role'))
            ->setId('roles')
            ->setName('roles[]')
            ->multiple()
            ->setValue($this->getValue('roles'));

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

        $fields['email'] = (new InputText())
            ->setLabel(__('E-mail'))
            ->setPlaceholder(__('Enter e-mail'))
            ->setNameAndId('email')
            ->setValue($this->getValue('email'))
            ->setValidationRules($emailValidator);

        $fields['avatar'] = (new InputFile())
            ->setLabel(__('Avatar'))
            ->setPlaceholder(__('Select Avatar'))
            ->setNameAndId('avatar')
            ->setValidationRules(
                [
                    'Optional',
                    'IsImage' => [
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                    ],
                ]
            );

        $fields['name'] = (new InputText())
            ->setLabel(__('Name'))
            ->setPlaceholder(__('Enter Name'))
            ->setNameAndId('name')
            ->setValidationRules(
                [
                    'StringLength' => ['max' => 250],
                ]
            );

        $fields['phone'] = (new InputText())
            ->setLabel(__('Phone'))
            ->setPlaceholder(__('Phone'))
            ->setNameAndId('phone')
            ->setValue($this->getValue('phone'))
            ->setValidationRules(
                [
                    'ModelNotExists' => [
                        'model' => User::class,
                        'field' => 'phone',
                    ],
                ]
            );

        $fields['telegram'] = (new InputText())
            ->setLabel(__('Telegram'))
            ->setPlaceholder(__('Telegram'))
            ->setNameAndId('additional_fields_telegram')
            ->setValue($this->getValue('additional_fields_telegram'));

        $fields['whatsapp'] = (new InputText())
            ->setLabel(__('WhatsApp'))
            ->setPlaceholder(__('WhatsApp'))
            ->setNameAndId('additional_fields_whatsapp')
            ->setValue($this->getValue('additional_fields_whatsapp'));

        $fields['birthday'] = (new InputText())
            ->setLabel(__('Birthday'))
            ->setNameAndId('birthday')
            ->setValue(format_date($this->getValue('birthday'), true));

        $fields['gender'] = (new Select())
            ->setOptions(
                [
                    [
                        'value' => 0,
                        'name'  => d__('system', 'Not specified'),
                    ],
                    [
                        'value' => 1,
                        'name'  => d__('system', 'Male'),
                    ],
                    [
                        'value' => 2,
                        'name'  => d__('system', 'Female'),
                    ],
                ]
            )
            ->setLabel(__('Gender'))
            ->setNameAndId('gender')
            ->setValue($this->getValue('gender'));

        $fields['website'] = (new InputText())
            ->setLabel(__('Website'))
            ->setPlaceholder(__('Website'))
            ->setNameAndId('additional_fields_website')
            ->setValue($this->getValue('additional_fields_website'));

        $fields['about'] = (new Textarea())
            ->setLabel(__('About'))
            ->setPlaceholder(__('About'))
            ->setNameAndId('additional_fields_about')
            ->setValue($this->getValue('additional_fields_about'));

        return $fields;
    }

    private function getRoles(): array
    {
        $roles = Role::query()->get()->map(function (Role $role) {
            return ['name' => $role->display_name, 'value' => $role->id];
        })->toArray();
        return [
            [
                'name'  => __('User'),
                'value' => 0,
            ],
            ...$roles,
        ];
    }
}
