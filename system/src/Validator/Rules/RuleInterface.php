<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

/**
 * A validation rule as the application states it: a typed value object, free of the engine
 * that will enforce it.
 *
 * The rules are what a form declares, and RuleCompiler turns them into the constraints of the
 * engine. The indirection is deliberate: binding the call sites directly to a vendor is what
 * made replacing the previous engine expensive, and this layer is what lets the next
 * replacement leave the forms untouched.
 *
 * A rule of our own may implement this interface on a Symfony Constraint directly — there is
 * nothing to isolate when the rule is ours and has to be rewritten either way.
 */
interface RuleInterface
{
    /**
     * The message overriding the default of the rule, or null to keep it.
     *
     * Belongs to the rule rather than to the validator call: the previous API took a message map
     * for the whole form, which applied an override to every field carrying that rule instead of
     * to the field that needed it.
     */
    public function message(): ?string;
}
