<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

use Johncms\Validator\RuleValidators\CaptchaValidator;
use Symfony\Component\Validator\Constraint;

/**
 * The value answers the captcha the site uses.
 *
 * What "answers" means belongs to the provider, not to the rule: the built-in one compares the
 * value with the code it kept in the session, a remote service is asked about the token. The
 * scope names the form the captcha guards, so two forms open at once do not share one answer.
 */
final class Captcha extends Constraint implements RequiresValueInterface
{
    public string $message;

    private readonly ?string $ruleMessage;

    public function __construct(
        public string $scope = 'default',
        private readonly bool $allowEmpty = false,
        ?string $message = null,
    ) {
        parent::__construct([]);

        $this->ruleMessage = $message;
        // The default the engine sees. Which message a visitor actually gets is decided by the
        // validator, from what the provider says went wrong.
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
