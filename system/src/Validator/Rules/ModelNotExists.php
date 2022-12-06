<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Validator\Rules;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laminas\Validator\AbstractValidator;

class ModelNotExists extends AbstractValidator
{
    public const ERROR_RECORD_FOUND = 'modelExists';

    protected array $messageTemplates = [
        self::ERROR_RECORD_FOUND => "A record matching the input was found",
    ];

    private string $model;

    private string $field;

    private mixed $exclude = null;

    public function isValid($value): bool
    {
        $this->setValue($value);

        try {
            $model = (new $this->model());

            if (is_callable($this->exclude)) {
                $model = $model->where($this->exclude);
            } elseif (is_array($this->exclude) && ! empty($this->exclude['field'])) {
                $model = $model->where($this->exclude['field'], '!=', $this->exclude['value']);
            }

            $model->where($this->field, $value)->firstOrFail();
            $this->error(self::ERROR_RECORD_FOUND);
            $isValid = false;
        } catch (ModelNotFoundException) {
            $isValid = true;
        }

        return $isValid;
    }

    /**
     * Set exclude parameter
     */
    public function setExclude(mixed $value): ModelNotExists
    {
        $this->exclude = $value;
        return $this;
    }

    /**
     * Set model parameter
     */
    public function setModel(string $value): ModelNotExists
    {
        $this->model = $value;
        return $this;
    }

    /**
     * Set field parameter
     */
    public function setField(string $value): ModelNotExists
    {
        $this->field = $value;
        return $this;
    }
}
