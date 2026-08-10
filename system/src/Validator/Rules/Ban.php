<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

use Johncms\Validator\RuleValidators\BanValidator;
use Symfony\Component\Validator\Constraint;

/**
 * The visitor carries none of the listed bans.
 *
 * Like Flood, it says nothing about a value and belongs under ValidationResult::FORM_KEY.
 */
final class Ban extends Constraint implements RuleInterface
{
    public string $message = 'You have a ban';

    private readonly ?string $ruleMessage;

    /**
     * @param list<int> $bans The ban types to look for.
     */
    public function __construct(
        public array $bans = [1],
        ?string $message = null,
    ) {
        parent::__construct([]);

        $this->ruleMessage = $message;

        if ($message !== null) {
            $this->message = $message;
        }
    }

    public function message(): ?string
    {
        return $this->ruleMessage;
    }

    public function validatedBy(): string
    {
        return BanValidator::class;
    }
}
