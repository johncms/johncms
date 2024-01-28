<?php

declare(strict_types=1);

namespace Johncms\Forms;

use Illuminate\Support\Arr;
use Johncms\Exceptions\ValidationException;
use Johncms\Forms\Inputs\AbstractInput;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Validator\Validator;

abstract class AbstractForm
{
    /** @var AbstractInput[] */
    protected array $formFields = [];
    protected ?array $requestValues = null;

    public function __construct(
        protected Request $request,
        protected array $values = []
    ) {
    }

    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    public function buildForm(): void
    {
        $this->formFields = $this->prepareFormFields();
    }

    /**
     * Check if there are values in the form. Can be used to determine if this is edit form.
     */
    public function hasValues(): bool
    {
        return ! empty($this->values);
    }

    /**
     * The method must return an array of form fields
     *
     * @return AbstractInput[]
     */
    abstract protected function prepareFormFields(): array;

    public function getValue(string $fieldName, mixed $default = null): mixed
    {
        return $this->request->getPost($fieldName, Arr::get($this->values, $fieldName, $default));
    }

    public function getValidationErrors(): array
    {
        $session = di(Session::class);
        return (array) $session->getFlash(Validator::VALIDATION_ERRORS_KEY);
    }

    public function getFormFields(): array
    {
        if (empty($this->formFields)) {
            $this->buildForm();
        }
        return $this->formFields;
    }

    public function validate(): void
    {
        if (empty($this->formFields)) {
            $this->buildForm();
        }
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
        foreach ($this->formFields as $formField) {
            if (! empty($formField->validationRules)) {
                $rules[$formField->name] = $formField->validationRules;
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
        foreach ($this->formFields as $formField) {
            $fieldName = $formField->name;
            if (str_ends_with($fieldName, '[]')) {
                $fieldName = mb_substr($fieldName, 0, mb_strlen($fieldName) - 2);
            }
            if ($formField->type === 'file') {
                $this->requestValues[$fieldName] = $this->request->getUploadedFiles()[$fieldName] ?? [];
            } else {
                $this->requestValues[$fieldName] = $this->request->getPost($fieldName);
            }
        }
        return $this->requestValues;
    }
}
