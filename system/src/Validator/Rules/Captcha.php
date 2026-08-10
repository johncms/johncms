<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

use Johncms\Validator\RuleValidators\CaptchaValidator;
use Symfony\Component\Validator\Constraint;

/**
 * The value matches the security code the session holds, compared case-insensitively.
 */
final class Captcha extends Constraint implements RequiresValueInterface
{
    public string $message;

    private readonly ?string $ruleMessage;

    public function __construct(
        public string $sessionField = 'code',
        private readonly bool $allowEmpty = false,
        ?string $message = null,
    ) {
        parent::__construct([]);

        $this->ruleMessage = $message;
        $this->message = $message ?? d__('system', 'The security code is not correct');
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
        return CaptchaValidator::class;
    }
}
