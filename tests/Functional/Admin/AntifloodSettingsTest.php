<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The settings of the antiflood: the form shows what is configured, and only those who manage
 * the site may open it.
 */
final class AntifloodSettingsTest extends FunctionalTestCase
{
    public function testTheFormShowsTheConfiguredModeAndDelays(): void
    {
        $content = $this->withConfig(
            ['johncms' => ['antiflood' => ['mode' => 2, 'day' => 15, 'night' => 120, 'dayfrom' => 8, 'dayto' => 21]]],
            fn(): string => (string) $this->handleRequest('/admin/antiflood', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent()
        );

        self::assertStringContainsString('value="2" id="antiflood-mode-2" checked', $content);
        self::assertStringContainsString('name="day" id="antiflood-day" value="15"', $content);
        self::assertStringContainsString('name="night" id="antiflood-night" value="120"', $content);
        self::assertStringContainsString('name="dayfrom" id="antiflood-dayfrom" value="8"', $content);
        self::assertStringContainsString('name="dayto" id="antiflood-dayto" value="21"', $content);
    }

    public function testAModeratorOfThePanelMayNeitherOpenNorSaveTheSettings(): void
    {
        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS], level: 60);

        $page = $this->handleRequest('/admin/antiflood', cookies: $this->actingAs($moderator));
        // Asked only once the page is closed: a save that got through would write the config file.
        self::assertSame(Response::HTTP_FORBIDDEN, $page->getStatusCode());

        $save = $this->postForm('/admin/antiflood', ['mode' => 4, 'night' => 300], $moderator);
        self::assertSame(Response::HTTP_FORBIDDEN, $save->getStatusCode());
    }
}
