<?php

declare(strict_types=1);

namespace Johncms\Personal\Forms;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Forms\AbstractForm;
use Johncms\Forms\Inputs\InputText;
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
                        'model' => User::class,
                        'field' => 'phone',
                        'exclude' => function ($query) {
                            return $query->when($this->userData?->id, function (Builder $query) {
                                $query->where('phone', '!=', '')->where('id', '!=', $this->userData->id);
                            });
                        },
                    ],
                ]
            );

        $fields['birthday'] = (new InputText())
            ->setLabel(__('Birthday'))
            ->setNameAndId('birthday')
            ->setValue($this->getValue('birthday'));

        return $fields;
    }

    public function getValue(string $fieldName, mixed $default = null)
    {
        if ($this->userData) {
            return parent::getValue($fieldName, $this->userData?->$fieldName);
        }
        return parent::getValue($fieldName, $default);
    }
}
