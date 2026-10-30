<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleFactories;

use Johncms\Validator\RuleConstraintFactoryInterface;
use Johncms\Validator\Rules\RuleInterface;
use Johncms\Validator\Rules\WebAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Url;

final readonly class WebAddressRuleFactory implements RuleConstraintFactoryInterface
{
    public static function ruleClass(): string
    {
        return WebAddress::class;
    }

    /**
     * @return list<Constraint>
     */
    public function create(RuleInterface $rule): array
    {
        if (! $rule instanceof WebAddress) {
            throw new \InvalidArgumentException('The factory was handed a rule it does not build.');
        }

        return [
            new Type(type: 'string', message: d__('system', 'Invalid type given. String expected')),
            new Url(
                message: d__('system', 'Enter a full address that starts with http:// or https://'),
                protocols: ['http', 'https'],
                requireTld: true,
            ),
        ];
    }
}
