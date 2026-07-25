<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\Forms;

use Johncms\Config\ConfigRepository;
use Johncms\Modules\Guestbook\Application\Forms\GuestbookForm;
use Johncms\Http\Request;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Users\User;
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
            'csrf_token'     => ' token ',
            'code'           => ' 123 ',
            'attached_files' => ['3', 7],
        ]);

        $form = new GuestbookForm($request, UserFactory::make(), new EditorContentNormalizer());
        $formData = $form->getFormData();

        self::assertSame('John', $formData['name']);
        self::assertSame('Hello world', $formData['message']);
        self::assertSame('token', $formData['csrf_token']);
        self::assertSame('123', $formData['code']);
        self::assertSame([3, 7], $formData['attached_files']);
    }

    public function testValidationRulesForValidUser(): void
    {
        $rules = $this->makeForm(UserFactory::make())->getValidationRules();

        self::assertArrayHasKey('message', $rules);
        self::assertArrayHasKey('csrf_token', $rules);
        self::assertArrayNotHasKey('name', $rules);
        self::assertArrayNotHasKey('code', $rules);
    }

    public function testValidationRulesForGuestIncludeNameAndCaptcha(): void
    {
        $rules = $this->makeForm(UserFactory::make(valid: false))->getValidationRules();

        self::assertArrayHasKey('name', $rules);
        self::assertSame(['min' => 3, 'max' => 25], $rules['name']['StringLength']);
        self::assertContains('Captcha', $rules['code']);
    }

    private function makeForm(User $user): GuestbookForm
    {
        return new GuestbookForm(new Request(), $user, new EditorContentNormalizer());
    }
}
