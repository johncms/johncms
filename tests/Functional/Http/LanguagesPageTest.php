<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Johncms\Config\ConfigRepository;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

final class LanguagesPageTest extends FunctionalTestCase
{
    public function testEveryLanguageOnTheDiskIsListedAndTheDefaultIsLockedOn(): void
    {
        // The list is replaced, not merged as withConfig() does: Ukrainian has its files on the
        // disk and stays off the list, whatever the configuration of the machine running this.
        $original = ConfigRepository::all();
        $config = $original;
        $config['johncms']['lng'] = 'ru';
        $config['johncms']['lng_list'] = ['ru' => ['name' => 'Русский', 'version' => 1.0]];
        ConfigRepository::init($config);

        try {
            $response = $this->handleRequest('/admin/languages', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
            $content = (string) $response->getContent();
        } finally {
            ConfigRepository::init($original);
        }

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertMatchesRegularExpression('#name="enabled\[\]" value="uk" id="enabled-uk" aria-label="[^"]+"\s+data-language-enabled#', $content);
        self::assertMatchesRegularExpression('#value="ru" id="enabled-ru" aria-label="[^"]+" checked disabled#', $content);
        self::assertMatchesRegularExpression('#name="lng" value="ru" id="default-ru" checked#', $content);
    }

    public function testTheTopBarSwitchesTheLanguageWithLinksAndNoDialog(): void
    {
        $content = $this->withConfig(
            ['johncms' => ['lng' => 'ru', 'lng_list' => ['ru' => ['name' => 'Русский', 'version' => 1.0], 'en' => ['name' => 'English', 'version' => 1.0]]]],
            fn (): string => (string) $this->handleRequest('/admin/settings', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent()
        );

        self::assertStringContainsString('href="?setlng=en" lang="en" data-language-link="en"', $content);
        self::assertMatchesRegularExpression('#class="dropdown-item active" href="\?setlng=ru"#', $content);
        self::assertStringNotContainsString('ajax_modal', $content);
    }

    public function testTheManagementOfLanguagePacksIsGone(): void
    {
        $response = $this->handleRequest('/admin/languages/manage', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
