<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The system settings screen. Read-only on purpose, like the other settings screens: saving
 * writes system.local.php, the configuration the rest of the suite runs with.
 */
final class SystemSettingsPageTest extends FunctionalTestCase
{
    public function testTheTimeOfTheSiteIsPickedFromTheShifts(): void
    {
        $content = $this->page();
        $shift = (int) config('johncms.timeshift', 0);

        self::assertStringContainsString('<select id="timeshift" name="timeshift"', $content);
        self::assertMatchesRegularExpression('#<option value="' . $shift . '" selected>UTC[+−]\d+ · \d\d:\d\d</option>#u', $content);
        // From UTC−12 to UTC+14.
        self::assertStringContainsString('<option value="-12"', $content);
        self::assertStringContainsString('<option value="14"', $content);
    }

    public function testTheSettingThatNothingReadsIsGone(): void
    {
        self::assertStringNotContainsString('name="gz"', $this->page());
    }

    public function testTheButtonsStayInView(): void
    {
        self::assertStringContainsString('admin-form-footer--sticky', $this->page());
    }

    public function testTheSettingsAreTabsOfOnePage(): void
    {
        foreach (['/admin/settings' => '/admin/settings', '/admin/settings/mail' => '/admin/settings/mail', '/admin/settings/sign-in' => '/admin/settings/sign-in'] as $page => $active) {
            $content = (string) $this->handleRequest($page, cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent();

            self::assertMatchesRegularExpression('#class="nav-link active" href="' . preg_quote($active, '#') . '"\s*aria-current="page"#', $content, $page);
            self::assertStringContainsString('href="/admin/settings/captcha"', $content, $page);
        }
    }

    public function testTheSignInServicesMovedFromTheSecuritySection(): void
    {
        $response = $this->handleRequest('/admin/auth/providers', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(404, $response->getStatusCode());
    }

    private function page(): string
    {
        $response = $this->handleRequest('/admin/settings', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return (string) $response->getContent();
    }
}
