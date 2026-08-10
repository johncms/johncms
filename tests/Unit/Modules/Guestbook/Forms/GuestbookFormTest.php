<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\Forms;

use Johncms\Config\ConfigRepository;
use Johncms\Modules\Guestbook\Application\Forms\GuestbookForm;
use Johncms\Http\Request;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Users\User;
use Johncms\Validator\Rules\Captcha;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidationResult;
use PHPUnit\Framework\TestCase;
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

        $form = new GuestbookForm(UserFactory::make(), new EditorContentNormalizer());
        $formData = $form->getFormData($request);

        self::assertSame('John', $formData['name']);
        self::assertSame('Hello world', $formData['message']);
        self::assertSame('123', $formData['code']);
        self::assertSame([3, 7], $formData['attached_files']);
    }

    public function testValidationRulesForValidUser(): void
    {
        $rules = $this->makeForm(UserFactory::make())->getValidationRules();

        self::assertArrayHasKey('message', $rules);
        // Flood and Ban are form-level rules: they belong to no field of the form.
        self::assertArrayHasKey(ValidationResult::FORM_KEY, $rules);
        self::assertArrayNotHasKey('name', $rules);
        self::assertArrayNotHasKey('code', $rules);
    }

    public function testValidationRulesForGuestIncludeNameAndCaptcha(): void
    {
        $rules = $this->makeForm(UserFactory::make(valid: false))->getValidationRules();

        self::assertArrayHasKey('name', $rules);

        $name = $rules['name'][0];
        self::assertInstanceOf(StringLength::class, $name);
        self::assertSame(3, $name->min);
        self::assertSame(25, $name->max);

        self::assertInstanceOf(Captcha::class, $rules['code'][0]);
    }

    private function makeForm(User $user): GuestbookForm
    {
        return new GuestbookForm($user, new EditorContentNormalizer());
    }
}
