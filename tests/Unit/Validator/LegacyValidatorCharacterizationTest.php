<?php

declare(strict_types=1);

namespace Tests\Unit\Validator;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Container\PSRContainerFactory;
use Johncms\Http\Session;
use Johncms\Security\AntifloodCheckerInterface;
use Johncms\Validator\Validator;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Records what the current validator does, before the engine under it is replaced.
 *
 * There were no tests on the validator at all, and the replacement changes exactly the parts
 * nobody stated out loud: whether an empty value is checked or skipped, whether a comparison is
 * strict, whether a failing chain reports one message or all of them. Swapping the engine with
 * that unwritten is how a regression ships unnoticed, so it is written here first and the same
 * expectations are what the replacement has to satisfy.
 */
final class LegacyValidatorCharacterizationTest extends TestCase
{
    private ?ContainerInterface $previousContainer = null;

    protected function setUp(): void
    {
        // The messages of the rules go through d__(), which only exists once a translator is
        // registered. Untranslated msgids are returned as-is, which is what the assertions use.
        TranslatorFunctions::register(new Translator());

        $this->previousContainer = $this->containerProperty()->getValue();
    }

    protected function tearDown(): void
    {
        $this->containerProperty()->setValue(null, $this->previousContainer);
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProviderExternal(RuleBehaviourCases::class, 'cases')]
    public function testRuleVerdict(string $rule, array $options, mixed $value, bool $expected): void
    {
        $ruleSet = $options === [] ? [$rule] : [$rule => $options];

        $validator = new Validator(['field' => $value], ['field' => $ruleSet]);

        self::assertSame($expected, $validator->isValid());
    }

    /**
     * The shape every consumer depends on: the flash session, ValidationException, the domain
     * exceptions of the modules and the field-errors component all read this array. The keys of
     * the inner array are the error codes of the rule, which nothing reads — the replacement is
     * allowed to drop them, and only the field keys and the messages have to survive.
     */
    public function testErrorsAreKeyedByFieldAndCarryTheMessages(): void
    {
        $validator = new Validator(
            ['name' => '', 'email' => 'not an address'],
            ['name' => ['NotEmpty'], 'email' => ['EmailAddress']]
        );

        self::assertFalse($validator->isValid());

        $errors = $validator->getErrors();

        self::assertSame(['name', 'email'], array_keys($errors));
        self::assertNotSame([], array_values($errors['name']));
        self::assertContainsOnly('string', array_values($errors['name']));
    }

    /**
     * A field that passes is absent from the errors rather than present and empty.
     */
    public function testAPassingFieldIsAbsentFromTheErrors(): void
    {
        $validator = new Validator(
            ['name' => 'John', 'email' => 'not an address'],
            ['name' => ['NotEmpty'], 'email' => ['EmailAddress']]
        );

        self::assertArrayNotHasKey('name', $validator->getErrors());
    }

    /**
     * The chain breaks on the first failure, so a field reports one message and not one per rule.
     * Symfony checks every constraint of a field by default — reproducing this is what keeps the
     * UI from showing three errors on one input after the migration.
     */
    public function testTheChainStopsAtTheFirstFailingRule(): void
    {
        $validator = new Validator(
            ['name' => ''],
            ['name' => ['NotEmpty', 'StringLength' => ['min' => 10]]]
        );

        self::assertFalse($validator->isValid());
        self::assertCount(1, $validator->getErrors()['name']);
    }

    public function testAFieldWithNoRulesIsNotValidated(): void
    {
        $validator = new Validator(['name' => '', 'nickname' => ''], ['name' => ['NotEmpty']]);

        self::assertArrayNotHasKey('nickname', $validator->getErrors());
    }

    /**
     * An unknown rule name is skipped instead of failing loudly. Recorded because it is a trap:
     * a typo in a rule name silently turns the field into an unvalidated one.
     */
    public function testAnUnknownRuleIsIgnored(): void
    {
        $validator = new Validator(['name' => ''], ['name' => ['ThereIsNoSuchRule']]);

        self::assertTrue($validator->isValid());
    }

    public function testFloodPassesWhenNothingWasPostedRecently(): void
    {
        $this->useContainer([AntifloodCheckerInterface::class => $this->antiflood(0)]);

        self::assertTrue((new Validator(['_form' => null], ['_form' => ['Flood']]))->isValid());
    }

    /**
     * The rule ignores the value it is given: it answers about the visitor, not about the field
     * it happens to hang on. The remaining seconds reach the message as %value%.
     */
    public function testFloodFailsWithTheRemainingSecondsInTheMessage(): void
    {
        $this->useContainer([AntifloodCheckerInterface::class => $this->antiflood(7)]);

        $validator = new Validator(['_form' => null], ['_form' => ['Flood']]);

        self::assertFalse($validator->isValid());
        self::assertStringContainsString('7', implode(' ', $validator->getErrors()['_form']));
    }

    public function testBanPassesForAVisitorWithoutTheListedBans(): void
    {
        $this->useContainer([\Johncms\Users\User::class => $this->userWithBans([])]);

        self::assertTrue((new Validator(['_form' => null], ['_form' => ['Ban' => ['bans' => [1, 13]]]]))->isValid());
    }

    public function testBanFailsForAVisitorCarryingOneOfTheListedBans(): void
    {
        $this->useContainer([\Johncms\Users\User::class => $this->userWithBans([1 => true])]);

        self::assertFalse((new Validator(['_form' => null], ['_form' => ['Ban' => ['bans' => [1, 13]]]]))->isValid());
    }

    public function testBanOnlyLooksAtTheListedBanTypes(): void
    {
        $this->useContainer([\Johncms\Users\User::class => $this->userWithBans([1 => true])]);

        self::assertTrue((new Validator(['_form' => null], ['_form' => ['Ban' => ['bans' => [13]]]]))->isValid());
    }

    public function testCaptchaComparesTheSessionCodeCaseInsensitively(): void
    {
        $this->useContainer([Session::class => $this->sessionWithCode('AbC')]);

        self::assertTrue((new Validator(['code' => 'abc'], ['code' => ['Captcha']]))->isValid());
        self::assertTrue((new Validator(['code' => 'ABC'], ['code' => ['Captcha']]))->isValid());
        self::assertFalse((new Validator(['code' => 'xyz'], ['code' => ['Captcha']]))->isValid());
    }

    public function testCaptchaFailsWhenTheSessionHoldsNoCode(): void
    {
        $this->useContainer([Session::class => new Session(new MockArraySessionStorage())]);

        self::assertFalse((new Validator(['code' => 'abc'], ['code' => ['Captcha']]))->isValid());
    }

    public function testModelNotExistsFailsWhenARowMatches(): void
    {
        $rules = ['text' => ['ModelNotExists' => ['model' => MatchingModelStub::class, 'field' => 'text']]];

        self::assertFalse((new Validator(['text' => 'hello'], $rules))->isValid());
    }

    public function testModelNotExistsPassesWhenNoRowMatches(): void
    {
        $rules = ['text' => ['ModelNotExists' => ['model' => EmptyModelStub::class, 'field' => 'text']]];

        self::assertTrue((new Validator(['text' => 'hello'], $rules))->isValid());
    }

    /**
     * Nothing guards the value first, so a null goes to the query as-is and the rule answers
     * about whatever the database says. This is the empty-value trap of the migration plan seen
     * from the other side: here an empty value is checked rather than skipped.
     */
    public function testModelNotExistsQueriesEvenAnEmptyValue(): void
    {
        $rules = ['text' => ['ModelNotExists' => ['model' => MatchingModelStub::class, 'field' => 'text']]];

        self::assertFalse((new Validator(['text' => null], $rules))->isValid());
    }

    private function antiflood(int $remainingSeconds): AntifloodCheckerInterface
    {
        return new class ($remainingSeconds) implements AntifloodCheckerInterface {
            public function __construct(private readonly int $remainingSeconds)
            {
            }

            public function isFlood(): bool
            {
                return $this->remainingSeconds > 0;
            }

            public function getRemainingSeconds(): int
            {
                return $this->remainingSeconds;
            }
        };
    }

    /**
     * A double rather than the User model: the rule only reads the ban list, while building the
     * Eloquent model would need a database connection.
     *
     * @param array<int, bool> $bans
     */
    private function userWithBans(array $bans): stdClass
    {
        $user = new stdClass();
        $user->ban = $bans;

        return $user;
    }

    private function sessionWithCode(string $code): Session
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('code', $code);

        return $session;
    }

    /**
     * The rules resolve their dependencies through di() at call time, so the only way to isolate
     * them is to swap the container the helper reads. Restored in tearDown().
     *
     * @param array<string, object> $services
     */
    private function useContainer(array $services): void
    {
        $container = new Container();
        foreach ($services as $id => $service) {
            $container->set($id, $service);
        }

        $this->containerProperty()->setValue(null, $container);
    }

    private function containerProperty(): \ReflectionProperty
    {
        $property = (new ReflectionClass(PSRContainerFactory::class))->getProperty('containerInstance');
        $property->setAccessible(true);

        return $property;
    }
}

/**
 * Stands in for an Eloquent model whose query finds a row.
 */
final class MatchingModelStub
{
    public function where(mixed ...$arguments): self
    {
        return $this;
    }

    public function firstOrFail(): self
    {
        return $this;
    }
}

/**
 * Stands in for an Eloquent model whose query finds nothing.
 */
final class EmptyModelStub
{
    public function where(mixed ...$arguments): self
    {
        return $this;
    }

    public function firstOrFail(): never
    {
        throw new ModelNotFoundException();
    }
}
