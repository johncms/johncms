<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

use Closure;
use Johncms\Validator\RuleValidators\ModelExistsValidator;
use Symfony\Component\Validator\Constraint;

/**
 * A row of the model matches the value.
 */
final class ModelExists extends Constraint implements RequiresValueInterface
{
    public string $message;

    private readonly ?string $ruleMessage;

    /**
     * @param class-string $model
     * @param Closure|array<string, mixed>|null $exclude A query modifier narrowing the lookup.
     */
    public function __construct(
        public string $model,
        public string $field,
        public Closure|array|null $exclude = null,
        private readonly bool $allowEmpty = false,
        ?string $message = null,
    ) {
        parent::__construct([]);

        $this->ruleMessage = $message;
        $this->message = $message ?? d__('system', 'No record matching the input was found');
    }

    public function allowEmpty(): bool
    {
        return $this->allowEmpty;
    }

    public function message(): ?string
    {
        return $this->ruleMessage;
    }

    public function validatedBy(): string
    {
        return ModelExistsValidator::class;
    }
}
