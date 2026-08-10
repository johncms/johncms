<?php

declare(strict_types=1);

namespace Johncms\Validator;

use Johncms\Validator\Rules\RuleInterface;

interface ValidatorInterface
{
    /**
     * @param array<string, mixed>              $data
     * @param array<string, list<RuleInterface>> $rules
     */
    public function validate(array $data, array $rules): ValidationResult;
}
