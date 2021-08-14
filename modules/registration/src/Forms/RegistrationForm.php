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

use Johncms\Exceptions\ValidationException;
use Johncms\Forms\Inputs\AbstractInput;
use Johncms\Forms\Inputs\InputPassword;
use Johncms\Forms\Inputs\InputText;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Laminas\Validator\Hostname;

class RegistrationForm
{
    /** @var AbstractInput[] */
    protected array $formFields = [];

    protected ?array $requestValues = null;

    protected Request $request;

    public function __construct()
    {
        $this->request = di(Request::class);
        $this->prepareFormFields();
    }

    protected function prepareFormFields(): void
    {
        $this->formFields = [
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
        ];
    }

    public function getValue(string $fieldName, mixed $default = null)
    {
        return $this->request->getPost($fieldName, $default);
    }

    public function getValidationErrors(): array
    {
        $session = di(Session::class);
        return (array) $session->getFlash(Validator::VALIDATION_ERRORS_KEY);
    }

    public function getFormFields(): array
    {
        return $this->formFields;
    }

    public function validate(): void
    {
        $rules = $this->collectValidationRules();
        $values = $this->getRequestValues();
        $validator = new Validator($values, $rules);
        if (! $validator->isValid()) {
            throw ValidationException::withErrors($validator->getErrors());
        }
    }

    protected function collectValidationRules(): array
    {
        $rules = [];
        foreach ($this->formFields as $key => $formField) {
            if (! empty($formField->validationRules)) {
                $rules[$key] = $formField->validationRules;
            }
        }
        return $rules;
    }

    public function getRequestValues(): array
    {
        if ($this->requestValues !== null) {
            return $this->requestValues;
        }

        $this->requestValues = [];
        foreach ($this->formFields as $key => $formField) {
            $this->requestValues[$key] = $this->request->getPost($formField->name);
        }
        return $this->requestValues;
    }
}
