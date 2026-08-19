<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\Forms;

use Johncms\Captcha\CaptchaManager;
use Johncms\Captcha\CaptchaProviderRegistry;
use Johncms\Captcha\Providers\ImageCaptchaProvider;
use Johncms\Config\ConfigRepository;
use Johncms\Http\Session;
use Johncms\Modules\Guestbook\Application\Forms\GuestbookForm;
use Johncms\Http\Request;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Auth\CurrentUser;
use Johncms\Validator\Rules\Captcha;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidationResult;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\Support\CurrentUserFactory;
use Tests\Support\UserFactory;

final class GuestbookFormTest extends TestCase
{
    protected function setUp(): void
    {
        ConfigRepository::init([]);
    }

    public function testFormDataIsTrimmedAndNormalized(): void
    {
        $request = new Request([], [
            'name'           => '  John  ',
            'message'        => '<p>&nbsp;</p> Hello world ',
            'code'           => ' 123 ',
            'attached_files' => ['3', 7],
        ]);

        $form = $this->makeForm(CurrentUserFactory::withProfile(UserFactory::make()));
        $formData = $form->getFormData($request);

        self::assertSame('John', $formData['name']);
        self::assertSame('Hello world', $formData['message']);
        self::assertSame('123', $formData['code']);
        self::assertSame([3, 7], $formData['attached_files']);
    }

    public function testValidationRulesForValidUser(): void
    {
        $rules = $this->makeForm(CurrentUserFactory::withProfile(UserFactory::make()))->getValidationRules();

        self::assertArrayHasKey('message', $rules);
        // Flood and Ban are form-level rules: they belong to no field of the form.
        self::assertArrayHasKey(ValidationResult::FORM_KEY, $rules);
        self::assertArrayNotHasKey('name', $rules);
        self::assertArrayNotHasKey('code', $rules);
    }

    public function testValidationRulesForGuestIncludeNameAndCaptcha(): void
    {
        $rules = $this->makeForm(CurrentUserFactory::guest())->getValidationRules();

        self::assertArrayHasKey('name', $rules);

        $name = $rules['name'][0];
        self::assertInstanceOf(StringLength::class, $name);
        self::assertSame(3, $name->min);
        self::assertSame(25, $name->max);

        $captcha = $rules['code'][0];
        self::assertInstanceOf(Captcha::class, $captcha);
        self::assertSame(GuestbookForm::CAPTCHA_SCOPE, $captcha->scope);
    }

    private function makeForm(CurrentUser $user): GuestbookForm
    {
        $captcha = new CaptchaManager(
            new CaptchaProviderRegistry(
                [new ImageCaptchaProvider(new Session(new MockArraySessionStorage()), new NullLogger())]
            ),
            new NullLogger(),
        );

        return new GuestbookForm($user, new EditorContentNormalizer(), $captcha);
    }
}
