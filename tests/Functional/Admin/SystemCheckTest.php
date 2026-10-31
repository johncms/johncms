<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Dom\HTMLDocument;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Admin\Application\Services\AdminPermissions;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

final class SystemCheckTest extends FunctionalTestCase
{
    public function testThePageIncludesCoreAndModuleChecksWithoutRunningWriteProbes(): void
    {
        $user = FunctionalUserFactory::createWithPermissions([AdminPermissions::SYSTEM_CHECK]);
        $response = $this->handleRequest('/admin/system_check', cookies: $this->actingAs($user), catch: false);
        self::assertSame(200, $response->getStatusCode());
        $page = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);

        foreach (['php.version', 'php.extension.gd', 'database.version', 'database.migrations', 'scheduler.heartbeat', 'uploads.editor_limit', 'security.legacy_passwords', 'mail.failed'] as $id) {
            self::assertNotNull($page->querySelector('[data-check-id="' . $id . '"]'), $id);
        }
        self::assertNull($page->querySelector('[data-check-id="cache.round_trip"]'));
        self::assertNull($page->querySelector('[data-check-id^="storage.round_trip."]'));
        self::assertNotSame('', $page->querySelector('form[action="/admin/system_check"] input[name="csrf_token"]')?->getAttribute('value'));
    }

    public function testOtherAdministratorsCannotOpenOrRunTheChecks(): void
    {
        $user = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);
        self::assertSame(403, $this->handleRequest('/admin/system_check', cookies: $this->actingAs($user))->getStatusCode());
        self::assertSame(403, $this->postForm('/admin/system_check', ['run_probes' => '1'], $user)->getStatusCode());
    }

    public function testActiveChecksRequireACsrfToken(): void
    {
        $user = FunctionalUserFactory::createWithPermissions([AdminPermissions::SYSTEM_CHECK]);
        $response = $this->handleRequest('/admin/system_check', 'POST', ['run_probes' => '1'], cookies: $this->actingAs($user));
        self::assertSame(403, $response->getStatusCode());
    }
}
