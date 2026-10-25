<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Johncms\Auth\Authorization\CorePermissions;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The settings of the news in the panel: the form shows what is configured, and only those who
 * manage the news get there.
 *
 * Saving writes config/autoload/news.local.php, so it is covered by the test of the use case
 * rather than here.
 */
final class AdminNewsSettingsTest extends FunctionalTestCase
{
    public function testTheFormShowsTheConfiguredSettings(): void
    {
        $content = $this->withConfig(
            ['news' => [
                'homepage_show'         => true,
                'homepage_quantity'     => 7,
                'homepage_days'         => 14,
                'title'                 => 'Our news',
                'meta_keywords'         => 'news, cms',
                'meta_description'      => 'What happens',
                'section_title'         => 'Section #section_name#',
                'article_meta_keywords' => '#article_name#, news',
            ]],
            fn (): string => (string) $this->handleRequest('/admin/news/settings', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent()
        );

        self::assertMatchesRegularExpression('~name="homepage_show" value="1"\s+id="homepage_show" checked~', $content);
        self::assertStringContainsString('value="7"', $content);
        self::assertStringContainsString('value="14"', $content);
        self::assertStringContainsString('value="Our news"', $content);
        self::assertStringContainsString('value="news, cms"', $content);
        self::assertStringContainsString('>What happens</textarea>', $content);
        self::assertStringContainsString('value="Section #section_name#"', $content);
        self::assertStringContainsString('value="#article_name#, news"', $content);
    }

    public function testAMemberOfThePanelWithoutThePermissionIsNotLetIn(): void
    {
        $member = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS], level: 60);

        $response = $this->handleRequest('/admin/news/settings', cookies: $this->actingAs($member));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
