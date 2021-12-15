<?php

declare(strict_types=1);

namespace Johncms\Personal\Forms;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Forms\AbstractForm;
use Johncms\Forms\Inputs\InputPassword;
use Johncms\Forms\Inputs\InputText;
use Johncms\Forms\Inputs\Select;
use Johncms\Forms\Inputs\Textarea;
use Johncms\Users\User;
use Laminas\Validator\Hostname;

class ProfileForm extends AbstractForm
{
    protected ?User $userData = null;

    public function __construct(?int $id = null)
    {
        if ($id) {
            $this->userData = User::query()->findOrFail($id);
        }
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function prepareFormFields(): array
    {
        $fields = [];

        $fields['name'] = (new InputText())
            ->setLabel(__('Name'))
            ->setPlaceholder(__('Name'))
            ->setNameAndId('name')
            ->setValue($this->getValue('name'));

        $fields['status'] = (new InputText())
            ->setLabel(__('Status'))
            ->setNameAndId('additional_fields_status')
            ->setValue($this->getValue('additional_fields_status'));

        if (config('registration.show_email', true)) {
            $emailValidator = [
                'ModelNotExists' => [
                    'model'   => User::class,
                    'field'   => 'email',
                    'exclude' => function ($query) {
                        return $query->when($this->userData?->id, function (Builder $query) {
                            $query->where('email', '!=', '')->where('id', '!=', $this->userData->id);
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

            $confirmation = config('registration.email_confirmation', false);

            $fields['email'] = (new InputText())
                ->setLabel(__('E-mail'))
                ->setPlaceholder(__('Enter your e-mail address'))
                ->setNameAndId('email')
                ->setHelpText($confirmation ? __('When changing, the new email address will need to be confirmed.') : '')
                ->setValue($this->getValue('email'))
                ->setValidationRules($emailValidator);
        }

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
                            return $query->when($this->userData?->id, function (Builder $query) {
                                $query->where('phone', '!=', '')->where('id', '!=', $this->userData->id);
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

        $fields['password'] = (new InputPassword())
            ->setLabel(__('New password'))
            ->setPlaceholder(__('New password'))
            ->setHelpText(__('If you want to change your password, fill in this field'))
            ->setNameAndId('password');

        return $fields;
    }

    public function getValue(string $fieldName, mixed $default = null)
    {
        if ($this->userData) {
            // Additional fields
            if (str_contains($fieldName, 'additional_fields_')) {
                $field = substr($fieldName, 18); // 18 - length of "additional_fields_"
                return parent::getValue($fieldName, $this->userData?->additional_fields?->$field);
            }
            // Base fields
            return parent::getValue($fieldName, $this->userData?->$fieldName);
        }
        return parent::getValue($fieldName, $default);
    }

    public function getRequestValues(): array
    {
        $requestValues = parent::getRequestValues();
        $modifiedValues = [];
        foreach ($requestValues as $key => $requestValue) {
            if (str_contains($key, 'additional_fields_')) {
                $modifiedValues['additional_fields'][substr($key, 18)] = $requestValue;
            } else {
                $modifiedValues[$key] = $requestValue;
            }
        }
        return $modifiedValues;
    }
}
