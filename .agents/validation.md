# Validation

Form validation runs on `symfony/validator`, behind an API of the project: a form declares
typed rule objects, and `RuleCompiler` turns them into the constraints of the engine. The call
sites never name a vendor class, so the engine can be replaced again without touching them.

## Using the validator

Inject `Johncms\Validator\ValidatorInterface` — never build a validator with `new`.

```php
final readonly class GuestbookController
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $result = $this->validator->validate(
            ['message' => $request->body('message', '')],
            ['message' => [new StringLength(min: 4, max: 16000)]]
        );

        if (! $result->isValid()) {
            return $this->render(['errors' => $result->getErrors()]);
        }
        // …
    }
}
```

`ValidationResult` is immutable:

* `isValid()`, `getErrors()` — `array<string, list<string>>`, keyed by field name;
* `hasError($field)`, `getFirstError($field)`;
* `withError($field, $message)` — a domain failure found after validation (a cycle in a tree, a
  name taken while the form was open) joins the result instead of unpacking it into an array;
* `merge($other)`;
* `throwIfInvalid($exceptionFactory)` — for the use cases that answer with a domain exception.

### Errors of the form itself

A rule about the visitor rather than about a field — `Flood`, `Ban` — goes under
`ValidationResult::FORM_KEY` (`_form`), and the templates render that block above the form.

```php
$rules = [
    'message'                  => [new StringLength(min: 4)],
    ValidationResult::FORM_KEY => [new Flood(), new Ban(bans: [1, 13])],
];
```

## The null policy

**A field carrying a rule is required, unless the rule says otherwise.**

Most built-in Symfony validators return early on `null` and `''`, leaving requiredness to a
separate `NotBlank`. The engine this replaced did the opposite. Rather than let that difference
turn "this field is checked" into "it is checked when it is not empty", `RuleCompiler` puts a
`NotBlank` in front of every rule implementing `RequiresValueInterface`.

An optional field says so explicitly:

```php
'meta_keywords' => [new StringLength(max: 250, allowEmpty: true)],
```

Write `allowEmpty` deliberately. It is the difference between a field a visitor may leave blank
and one they may not, and it is the only place that difference is recorded.

## Adding a rule

### Wrapping a built-in constraint

Two classes: a value object in `system/src/Validator/Rules/` and a factory in
`system/src/Validator/RuleFactories/`.

```php
final readonly class StringLength implements RequiresValueInterface
{
    public function __construct(
        public ?int $min = null,
        public ?int $max = null,
        private bool $allowEmpty = false,
        private ?string $message = null,
    ) {
    }

    public function allowEmpty(): bool
    {
        return $this->allowEmpty;
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
```

```php
final readonly class StringLengthRuleFactory implements RuleConstraintFactoryInterface
{
    public static function ruleClass(): string
    {
        return StringLength::class;
    }

    /** @return list<Constraint> */
    public function create(RuleInterface $rule): array
    {
        // …
    }
}
```

The factory is registered by the `johncms.validator.rule_factory` tag, which
`system/config/services.php` applies to everything implementing the interface. A module puts
both classes in its own `src/` and needs no change to the core.

### A rule of the project

When the check is ours — a session, the visitor, a database lookup — there is nothing to isolate
from the vendor, so the rule *is* the constraint: it extends
`Symfony\Component\Validator\Constraint`, implements `RuleInterface`, and names its validator in
`validatedBy()`. The compiler passes such a rule through without a factory.

```php
final class Captcha extends Constraint implements RequiresValueInterface
{
    public string $message;

    public function __construct(/* … */)
    {
        parent::__construct([]);
        $this->message = $message ?? d__('system', 'The security code is not correct');
    }

    public function validatedBy(): string
    {
        return CaptchaValidator::class;
    }
}
```

Its `ConstraintValidator` lives in `system/src/Validator/RuleValidators/` and takes its
dependencies **through the constructor** — it is resolved from the container by
`ContainerConstraintValidatorFactory`. Never call `di()` inside a validator: that is what made
the previous rules untestable without booting the whole application.

## Messages

Messages are the project's own, on the msgids the catalogs already carry. The built-in catalogs
of `symfony/validator` are not loaded at all — see `Translation\GettextTranslator` for why.

Rules for a new message:

* it must reach the scanner as a **literal `d__('system', '…')` call** — in the constructor of
  the rule or in its factory. A default written as a property initializer is invisible to
  `composer translate-scan`, and the string silently stays untranslated;
* placeholders follow the msgids already in use: `%min%`, `%max%`, `%value%`, `%hostname%`.
  `GettextTranslator` also fills the Symfony spelling (`{{ limit }}`, `{{ value }}`), so a
  built-in constraint keeps working;
* a runtime value is set by the validator, not baked into the message:
  `->setParameter('%value%', $seconds)`;
* per-call overrides belong to the rule that needs them (`new Identical(token: '1', message: …)`),
  never to the form as a whole.

Then follow `.agents/localization.md`: `composer translate-scan`, then `composer translate`.

## Traps

* **A field stops at its first failing rule.** `RuleCompiler` wraps the rules of a field in
  `Sequentially`. Symfony would otherwise check every constraint and show three messages under
  one input.
* **`InArray` compares loosely**, unlike Symfony's `Choice`: a select posts `"1"` and has to keep
  matching a haystack of integer ids. Ask for `strict: true` when the comparison should be exact.
* **`StringLength` rejects a non-string** instead of measuring it, which `Length` alone does not.
* **The rules are excluded from the container** (`system/src/Validator/Rules`): they are value
  objects with scalar constructor arguments, and autowiring them breaks the compilation of the
  whole container. Their *validators* are ordinary services and must stay public.
* **Cross-field rules are possible but unwritten.** The whole data array is validated against one
  `Collection`, so a `ConstraintValidator` can reach the other fields through
  `$this->context->getRoot()`. Today the rules needing a neighbouring field take it through a
  closure instead (`exclude:`).

## Tests

`tests/Unit/Validator/RuleBehaviourCases.php` holds the behaviour of the value-only rules as a
provider that names rules rather than classes. It was recorded from the previous engine and is
what `NewValidatorCharacterizationTest` checks the current one against. A verdict that changes
has to change that file — which is how a regression is kept from passing as a refactoring.
