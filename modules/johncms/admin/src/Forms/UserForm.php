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

use Illuminate\Database\Eloquent\Builder;
use Johncms\Forms\AbstractForm;
use Johncms\Forms\Inputs\InputFile;
use Johncms\Forms\Inputs\InputHidden;
use Johncms\Forms\Inputs\InputPassword;
use Johncms\Forms\Inputs\InputText;
use Johncms\Forms\Inputs\Select;
use Johncms\Forms\Inputs\Textarea;
use Johncms\Http\Request;
use Johncms\Users\Role;
use Johncms\Users\User;
use Laminas\Validator\Hostname;

class UserForm extends AbstractForm
{
    public function __construct(
        private ?User $user = null
    ) {
        $request = di(Request::class);
        parent::__construct($request);
    }

    protected function prepareFormFields(): array
    {
        $fields = [];

        $fields['id'] = (new InputHidden())
            ->setNameAndId('id')
            ->setValue($this->getValue('id'));

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
                        'model'   => User::class,
                        'field'   => 'login',
                        'exclude' => function ($query) {
                            return $query->when($this->user?->id, function (Builder $query) {
                                $query->where('login', '!=', '')->where('id', '!=', $this->user->id);
                            });
                        },
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
                    $this->user?->id ? 'Optional' : 'NotEmpty',
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
                'model'   => User::class,
                'field'   => 'email',
                'exclude' => function ($query) {
                    return $query->when($this->user?->id, function (Builder $query) {
                        $query->where('email', '!=', '')->where('id', '!=', $this->user->id);
                    });
                },
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
            ->setCurrentFile($this->getCurrentAvatar())
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
            ->setValue($this->getValue('name'))
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
                        'model'   => User::class,
                        'field'   => 'phone',
                        'exclude' => function ($query) {
                            return $query->when($this->user?->id, function (Builder $query) {
                                $query->where('phone', '!=', '')->where('id', '!=', $this->user->id);
                            });
                        },
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

    public function getValue(string $fieldName, mixed $default = null): mixed
    {
        if ($this->user) {
            if ($fieldName === 'roles') {
                return $this->user->roles->pluck('id')->toArray();
            }
            // Additional fields
            if (str_contains($fieldName, 'additional_fields_')) {
                $field = substr($fieldName, 18); // 18 - length of "additional_fields_"
                return parent::getValue($fieldName, $this->user->additional_fields?->$field);
            }
            // Base fields
            return parent::getValue($fieldName, $this->user->$fieldName);
        }

        return parent::getValue($fieldName, $default);
    }

    public function getRequestValues(): array
    {
        $requestValues = parent::getRequestValues();
        $modifiedValues = [];

        $delAvatar = $this->request->getPost('delete_avatar');
        if ($delAvatar) {
            $modifiedValues['delete_avatar'] = true;
        }

        foreach ($requestValues as $key => $requestValue) {
            if (str_contains($key, 'additional_fields_')) {
                $modifiedValues['additional_fields'][substr($key, 18)] = $requestValue;
            } else {
                $modifiedValues[$key] = $requestValue;
            }
        }
        return $modifiedValues;
    }

    private function getCurrentAvatar(): array
    {
        $avatar = $this->user?->avatar;
        if ($avatar) {
            return [
                'id'           => $avatar->id,
                'name'         => $avatar->name,
                'url'          => $avatar->url,
                'isImage'      => $avatar->is_image,
                'delInputName' => 'delete_avatar',
            ];
        }

        return [];
    }
}
