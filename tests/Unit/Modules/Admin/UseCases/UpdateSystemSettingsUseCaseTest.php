<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Johncms\Modules\Admin\Application\DTO\SystemSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateSystemSettingsUseCase;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSystemConfigRepository;

final class UpdateSystemSettingsUseCaseTest extends TestCase
{
    public function testTheWelcomeIsSavedAsPlainTextIndependentlyOfSeoAndOtherSettings(): void
    {
        $repository = new FakeSystemConfigRepository(['custom_module_setting' => 'keep']);
        $dto = new SystemSettingsDTO(
            theme: 'default',
            email: 'admin@example.com',
            timeShift: 0,
            copyright: 'Our community',
            homeUrl: 'https://example.com',
            maxFileSize: 1024,
            metaTitle: 'Community',
            metaKeywords: '',
            metaDescription: 'Search description',
            userEmailRequired: 0,
            userEmailConfirmation: 0,
            privacyPolicyUrl: '',
            termsOfUseUrl: '',
            personalDataPolicyUrl: '',
            cookiePolicyUrl: '',
            libraryComments: true,
            downloadsComments: true,
            registrationModeration: false,
            homepageWelcome: '<b>Welcome</b>',
        );

        (new UpdateSystemSettingsUseCase($repository))->execute($dto);

        self::assertSame('<b>Welcome</b>', $repository->saved['homepage_welcome']);
        self::assertSame('Search description', $repository->saved['meta_desc']);
        self::assertSame('keep', $repository->saved['custom_module_setting']);
    }
}
