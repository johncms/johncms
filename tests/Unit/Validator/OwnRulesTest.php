<?php

declare(strict_types=1);

namespace Tests\Unit\Validator;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Captcha\CaptchaManager;
use Johncms\Captcha\CaptchaProviderRegistry;
use Johncms\Captcha\Providers\ImageCaptchaProvider;
use Johncms\Http\Session;
use Johncms\Security\AntifloodCheckerInterface;
use Johncms\Validator\RuleCompiler;
use Johncms\Validator\RuleValidators\BanValidator;
use Tests\Support\CurrentUserFactory;
use Johncms\Validator\RuleValidators\CaptchaValidator;
use Johncms\Validator\RuleValidators\FloodValidator;
use Johncms\Validator\RuleValidators\InArrayValidator;
use Johncms\Validator\RuleValidators\MxRecordValidator;
use Johncms\Validator\Rules\Ban;
use Johncms\Validator\Rules\Captcha;
use Johncms\Validator\Rules\Flood;
use Johncms\Validator\Rules\InArray;
use Johncms\Validator\Rules\MxRecord;
use Johncms\Validator\Rules\RuleInterface;
use Johncms\Validator\SymfonyValidator;
use Johncms\Validator\Translation\GettextTranslator;
use Johncms\Validator\ValidationResult;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RequestStack;
use Johncms\Users\User;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;

/**
 * The rules of the project on the new engine.
 *
 * Their validators take their dependencies through a constructor and are resolved from the
 * container — the rules they replace called di() inside isValid(), which is why none of them
 * could be tested without booting the application.
 */
final class OwnRulesTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
    }

    public function testFloodPassesWhenNothingWasPostedRecently(): void
    {
        $result = $this->validate(new Flood(), null, [FloodValidator::class => new FloodValidator($this->antiflood(0))]);

        self::assertTrue($result->isValid());
    }

    /**
     * The remaining delay reaches the message: the parameter is set at validation time, not when
     * the rule is built.
     */
    public function testFloodReportsTheRemainingSeconds(): void
    {
        $result = $this->validate(new Flood(), null, [FloodValidator::class => new FloodValidator($this->antiflood(7))]);

        self::assertFalse($result->isValid());
        self::assertStringContainsString('7', (string) $result->getFirstError(ValidationResult::FORM_KEY));
    }

    public function testBanPassesForAVisitorWithoutTheListedBans(): void
    {
        $result = $this->validate(
            new Ban(bans: [1, 13]),
            null,
            [BanValidator::class => $this->banValidator([])]
        );

        self::assertTrue($result->isValid());
    }

    public function testBanFailsForAVisitorCarryingOneOfTheListedBans(): void
    {
        $result = $this->validate(
            new Ban(bans: [1, 13]),
            null,
            [BanValidator::class => $this->banValidator([1 => 1])]
        );

        self::assertFalse($result->isValid());
    }

    public function testBanOnlyLooksAtTheListedBanTypes(): void
    {
        $result = $this->validate(
            new Ban(bans: [13]),
            null,
            [BanValidator::class => $this->banValidator([1 => 1])]
        );

        self::assertTrue($result->isValid());
    }

    public function testCaptchaComparesTheSessionCodeCaseInsensitively(): void
    {
        self::assertTrue($this->validate(new Captcha(), 'abc', $this->captchaValidators('AbC'))->isValid());
        self::assertTrue($this->validate(new Captcha(), 'ABC', $this->captchaValidators('AbC'))->isValid());
        self::assertFalse($this->validate(new Captcha(), 'xyz', $this->captchaValidators('AbC'))->isValid());
    }

    public function testCaptchaFailsWhenTheSessionHoldsNoCode(): void
    {
        $session = new Session(new MockArraySessionStorage());

        self::assertFalse($this->validate(new Captcha(), 'abc', $this->validatorsFor($session))->isValid());
    }

    /**
     * The answer is spent by being checked: a picture answered once must not stay answerable, or
     * a captured submission could be replayed until it got through.
     */
    public function testCaptchaAnswersOnlyOnce(): void
    {
        $session = $this->sessionWithCode('AbC');
        $validators = $this->validatorsFor($session);

        self::assertTrue($this->validate(new Captcha(), 'abc', $validators)->isValid());
        self::assertFalse($this->validate(new Captcha(), 'abc', $validators)->isValid());
    }

    /**
     * Two forms open at once keep their own answers: one shared session key used to let the
     * later of them erase the code of the earlier.
     */
    public function testCaptchaScopesAreIndependent(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('captcha.guestbook', 'AbC');
        $session->set('captcha.registration', 'XyZ');
        $validators = $this->validatorsFor($session);

        self::assertTrue($this->validate(new Captcha(scope: 'guestbook'), 'abc', $validators)->isValid());
        self::assertTrue($this->validate(new Captcha(scope: 'registration'), 'xyz', $validators)->isValid());
    }

    /**
     * An empty code fails on the NotBlank the compiler puts in front of the rule rather than on
     * the comparison, so the visitor is told the field is required instead of being told their
     * code is wrong.
     */
    public function testAnEmptyCaptchaIsReportedAsAMissingValue(): void
    {
        $result = $this->validate(new Captcha(), '', $this->captchaValidators('AbC'));

        self::assertFalse($result->isValid());
        self::assertSame('Value is required and can\'t be empty', $result->getFirstError('field'));
    }

    /**
     * The comparison stays loose, unlike Symfony's own Choice: a select posting "1" has to keep
     * matching a haystack of integer ids.
     */
    public function testInArrayComparesLooselyByDefault(): void
    {
        $validators = [InArrayValidator::class => new InArrayValidator()];

        self::assertTrue($this->validate(new InArray(haystack: [1, 2]), '1', $validators)->isValid());
        self::assertTrue($this->validate(new InArray(haystack: ['1', '2']), 1, $validators)->isValid());
        self::assertFalse($this->validate(new InArray(haystack: [1, 2]), '1abc', $validators)->isValid());
    }

    public function testInArrayCanBeAskedForAStrictComparison(): void
    {
        $validators = [InArrayValidator::class => new InArrayValidator()];

        self::assertFalse($this->validate(new InArray(haystack: [1, 2], strict: true), '1', $validators)->isValid());
        self::assertTrue($this->validate(new InArray(haystack: [1, 2], strict: true), 1, $validators)->isValid());
    }

    /**
     * The address itself is the business of the Email constraint standing in front of this rule;
     * a value that is not an address at all must not produce a second message under the field.
     */
    public function testMxRecordSaysNothingAboutAValueWithoutAHost(): void
    {
        $validators = [MxRecordValidator::class => new MxRecordValidator()];

        self::assertTrue($this->validate(new MxRecord(), 'not an address', $validators)->isValid());
        self::assertTrue($this->validate(new MxRecord(), '', $validators)->isValid());
    }

    /**
     * @param array<class-string, object> $validators
     */
    private function validate(RuleInterface $rule, mixed $value, array $validators): ValidationResult
    {
        $container = new Container();
        foreach ($validators as $id => $validator) {
            $container->set($id, $validator);
        }

        $engine = Validation::createValidatorBuilder()
            ->setTranslator(new GettextTranslator())
            ->setConstraintValidatorFactory(new ContainerConstraintValidatorFactory($this->psr($container)))
            ->getValidator();

        $field = $rule instanceof Flood || $rule instanceof Ban ? ValidationResult::FORM_KEY : 'field';

        return (new SymfonyValidator($engine, new RuleCompiler([])))->validate([$field => $value], [$field => [$rule]]);
    }

    private function psr(Container $container): ContainerInterface
    {
        return new class ($container) implements ContainerInterface {
            public function __construct(private readonly Container $container)
            {
            }

            public function get(string $id): mixed
            {
                return $this->container->get($id);
            }

            public function has(string $id): bool
            {
                return $this->container->has($id);
            }
        };
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
     * The ban list of the real model is an accessor that queries the database; a subclass
     * answering it directly keeps the test off the connection.
     *
     * @param array<int, int> $bans
     */
    private function banValidator(array $bans): BanValidator
    {
        $user = new class extends User {
            /** @var array<int, int> */
            public array $activeBans = [];

            public function getBanAttribute(): array
            {
                return $this->activeBans;
            }
        };
        $user->activeBans = $bans;

        return new BanValidator(CurrentUserFactory::withProfile($user));
    }

    private function sessionWithCode(string $code): Session
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('captcha.default', $code);

        return $session;
    }

    /**
     * @return array<class-string, object>
     */
    private function captchaValidators(string $code): array
    {
        return $this->validatorsFor($this->sessionWithCode($code));
    }

    /**
     * The built-in provider over the given session, reached the way the application reaches it:
     * through the registry and the manager, so the wiring is part of what is tested.
     *
     * @return array<class-string, object>
     */
    private function validatorsFor(Session $session): array
    {
        $manager = new CaptchaManager(
            new CaptchaProviderRegistry([new ImageCaptchaProvider($session, new NullLogger())]),
            new NullLogger(),
        );

        return [CaptchaValidator::class => new CaptchaValidator($manager, new RequestStack())];
    }
}
