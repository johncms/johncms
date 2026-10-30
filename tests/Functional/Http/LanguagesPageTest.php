<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

final class LanguagesPageTest extends FunctionalTestCase
{
    public function testEveryLanguageOnTheDiskIsListedAndTheDefaultIsLockedOn(): void
    {
        // The override is merged into the configuration, so the language is taken off by nulling it.
        $this->withConfig(['johncms' => ['lng' => 'ru', 'lng_list' => ['uk' => null]]], function (): void {
            $response = $this->handleRequest('/admin/languages', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
            $content = (string) $response->getContent();

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            // A language with files but off the list is shown, turned off.
            self::assertMatchesRegularExpression('#name="enabled\[\]" value="uk" id="enabled-uk" aria-label="[^"]+"\s+data-language-enabled#', $content);
            self::assertMatchesRegularExpression('#value="ru" id="enabled-ru" aria-label="[^"]+" checked disabled#', $content);
            self::assertMatchesRegularExpression('#name="lng" value="ru" id="default-ru" checked#', $content);
        });
    }

    public function testTheManagementOfLanguagePacksIsGone(): void
    {
        $response = $this->handleRequest('/admin/languages/manage', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
