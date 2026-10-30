<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Modules\Consent\Domain\Models\Consent;
use Johncms\Modules\Consent\Domain\Models\ConsentLog;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

final class ConsentAdminTest extends FunctionalTestCase
{
    public function testListUsesTheAdminCardLayoutAndShowsConsentStatuses(): void
    {
        Consent::query()->create([
            'context' => 'registration',
            'language' => 'ru',
            'title' => 'Terms of service',
            'text' => 'Full terms',
            'version' => '2.0',
            'is_required' => true,
            'is_active' => true,
        ]);

        $response = $this->handleRequest('/admin/consents', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        $content = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('class="card"', $content);
        self::assertStringContainsString('Terms of service', $content);
        self::assertStringContainsString('data-admin-modal', $content);
        self::assertStringContainsString('bg-success-subtle', $content);
    }

    public function testEditFormGroupsFieldsAndExplainsConsentOptions(): void
    {
        $response = $this->handleRequest('/admin/consents/create', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        $content = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Параметры согласия', $content);
        self::assertStringContainsString('Текст согласия', $content);
        self::assertStringContainsString('Настройки поведения', $content);
        self::assertStringContainsString('Если включено, форму нельзя отправить без согласия посетителя.', $content);
        self::assertStringContainsString('Неактивные согласия не отображаются в формах.', $content);
    }

    public function testConsentLogUsesTheAdminCardLayoutAndShowsGuestAcceptance(): void
    {
        $consent = Consent::query()->create([
            'context' => 'registration',
            'language' => 'ru',
            'title' => 'Terms of service',
            'text' => 'Full terms',
            'version' => '2.0',
            'is_required' => true,
            'is_active' => true,
        ]);
        ConsentLog::query()->create([
            'user_id' => null,
            'consent_id' => $consent->id,
            'version' => '2.0',
            'ip_address' => '192.0.2.12',
            'accepted_at' => '2026-10-29 12:30:00',
        ]);

        $response = $this->handleRequest('/admin/consents/log', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        $content = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('class="card"', $content);
        self::assertStringContainsString('Terms of service', $content);
        self::assertStringContainsString('registration', $content);
        self::assertStringContainsString('192.0.2.12', $content);
        self::assertStringContainsString('Гость', $content);
        self::assertStringContainsString('admin-list-footer__total', $content);
    }

    public function testConsentLogNamesAnExistingAccountAndKeepsTheIdOfADeletedOne(): void
    {
        $consent = Consent::query()->create([
            'context' => 'registration',
            'language' => 'ru',
            'title' => 'Terms of service',
            'text' => 'Full terms',
            'version' => '2.0',
            'is_required' => true,
            'is_active' => true,
        ]);
        $member = FunctionalUserFactory::create(['name' => 'Consenting member']);
        foreach ([$member->id, 987654] as $userId) {
            ConsentLog::query()->create([
                'user_id' => $userId,
                'consent_id' => $consent->id,
                'version' => '2.0',
                'ip_address' => '192.0.2.12',
                'accepted_at' => '2025-03-14 12:30:00',
            ]);
        }

        $content = (string) $this->handleRequest('/admin/consents/log', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent();

        self::assertStringContainsString('Consenting member', $content);
        self::assertStringContainsString('href="/profile/' . $member->id . '"', $content);
        self::assertStringContainsString('987654', $content);
        self::assertStringNotContainsString('href="/profile/987654"', $content);
        self::assertStringContainsString('14 мар 2025', $content);
    }

    public function testDeleteConfirmationSupportsModalAndPlainPage(): void
    {
        $consent = Consent::query()->create([
            'context' => 'registration',
            'language' => 'ru',
            'title' => 'Terms of service',
            'text' => '',
            'version' => '1.0',
            'is_required' => false,
            'is_active' => false,
        ]);
        $cookies = $this->actingAs(FunctionalUserFactory::createSupervisor());

        $dialog = $this->handleRequest('/admin/consents/' . $consent->id . '/delete', server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'], cookies: $cookies);
        $page = $this->handleRequest('/admin/consents/' . $consent->id . '/delete', cookies: $cookies);

        self::assertSame(Response::HTTP_OK, $dialog->getStatusCode());
        self::assertStringStartsWith('<form class="modal-content"', trim((string) $dialog->getContent()));
        self::assertStringContainsString('Terms of service', (string) $dialog->getContent());
        self::assertSame(Response::HTTP_OK, $page->getStatusCode());
        self::assertStringContainsString('class="modal admin-dialog-page"', (string) $page->getContent());
    }
}
