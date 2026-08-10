<?php

declare(strict_types=1);

namespace Johncms\Validator;

use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * The validator of the application, backed by symfony/validator.
 *
 * The whole data array is validated in one call, against a Collection, rather than field by
 * field. That is what gives a custom validator access to the other fields through
 * $this->context->getRoot(): the previous engine handed a rule the value of one field and
 * nothing else, which is why the rules needing a neighbouring field smuggle it in through a
 * closure. Nothing needs cross-field rules today — this only keeps the door open for them.
 */
final readonly class SymfonyValidator implements ValidatorInterface
{
    public function __construct(
        private SymfonyValidatorInterface $validator,
        private RuleCompiler $compiler,
    ) {
    }

    /**
     * @param array<string, mixed>              $data
     * @param array<string, list<Rules\RuleInterface>> $rules
     */
    public function validate(array $data, array $rules): ValidationResult
    {
        $fields = [];
        foreach ($rules as $field => $fieldRules) {
            $fields[$field] = $this->compiler->compile($fieldRules);
            // A field the form declares rules for but did not submit is validated as null rather
            // than reported as missing: a checkbox nobody ticked is simply an empty value.
            $data[$field] ??= null;
        }

        $violations = $this->validator->validate(
            $data,
            new Collection(fields: $fields, allowExtraFields: true, allowMissingFields: true)
        );

        $errors = [];
        foreach ($violations as $violation) {
            $errors[$this->fieldOf($violation->getPropertyPath())][] = (string) $violation->getMessage();
        }

        return new ValidationResult($errors);
    }

    /**
     * A Collection reports the path of a field as "[name]"; the consumers of the result expect
     * the bare field name, which is what the templates and the domain exceptions key on.
     */
    private function fieldOf(string $propertyPath): string
    {
        return trim($propertyPath, '[]');
    }
}
