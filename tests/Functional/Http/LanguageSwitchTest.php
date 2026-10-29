<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Tests\Functional\FunctionalTestCase;

/**
 * The language modal: what the visitor picks there is the language of the pages that follow.
 */
final class LanguageSwitchTest extends FunctionalTestCase
{
    private const array LANGUAGES = [
        'en' => ['name' => 'English', 'version' => 1.0],
        'ru' => ['name' => 'Русский', 'version' => 1.0],
    ];

    public function testTheLanguagePickedInTheModalIsRemembered(): void
    {
        $this->withConfig(['johncms' => ['lng' => 'en', 'lng_list' => self::LANGUAGES]], function (): void {
            $this->handleRequest('/language/', 'POST', $this->withCsrfToken(['setlng' => 'ru']));

            self::assertStringContainsString('<html lang="ru"', (string) $this->handleRequest('/')->getContent());
        });
    }

    public function testALanguageTheSiteDoesNotHaveIsIgnored(): void
    {
        $this->withConfig(['johncms' => ['lng' => 'en', 'lng_list' => self::LANGUAGES]], function (): void {
            $this->handleRequest('/language/', 'POST', $this->withCsrfToken(['setlng' => 'klingon']));

            self::assertStringContainsString('<html lang="en"', (string) $this->handleRequest('/')->getContent());
        });
    }
}
