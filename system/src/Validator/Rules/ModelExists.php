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

class ModelExists extends AbstractValidator
{
    public const NOT_FOUND = 'modelNotFound';

    protected array $messageTemplates = [
        self::NOT_FOUND => "No record matching the input was found",
    ];

    private string $model;

    private string $field;

    public function isValid($value): bool
    {
        $this->setValue($value);
        $isValid = true;

        try {
            $model = (new $this->model());
            $model->where($this->field, $value)->firstOrFail();
        } catch (ModelNotFoundException) {
            $this->error(self::NOT_FOUND);
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Set model parameter
     *
     * @param string $value
     * @return $this
     */
    public function setModel(string $value): ModelExists
    {
        $this->model = $value;
        return $this;
    }

    /**
     * Set field parameter
     *
     * @param string $value
     * @return $this
     */
    public function setField(string $value): ModelExists
    {
        $this->field = $value;
        return $this;
    }
}
