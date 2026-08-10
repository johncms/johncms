<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

/**
 * A rule that treats an empty value as a failure unless the field is explicitly optional.
 *
 * Most built-in Symfony validators return early on null and '', leaving the requiredness to a
 * separate NotBlank; the previous engine did the opposite and failed on an empty value almost
 * everywhere. A straight swap would therefore have turned "this field has a rule, so it is
 * checked" into "it is checked when it is not empty" — silently, and for every rule at once.
 *
 * So a rule implementing this interface is compiled with a NotBlank in front of it, and a field
 * opts out of that explicitly, by constructing the rule with allowEmpty: true.
 */
interface RequiresValueInterface extends RuleInterface
{
    public function allowEmpty(): bool;
}
