<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The screens the forum is managed from live in the forum module and are reached through the
 * gates of the panel. Both halves are worth a test: the routes belong to one module while the
 * guard and the layout belong to another, and that is exactly the seam a move can break.
 */
final class ForumAdminSectionTest extends FunctionalTestCase
{
    public static function screenProvider(): iterable
    {
        yield 'dashboard' => ['/admin/forum'];
        yield 'structure' => ['/admin/forum/structure'];
        yield 'hidden topics' => ['/admin/forum/hidden-topics'];
        yield 'hidden posts' => ['/admin/forum/hidden-posts'];
    }

    #[DataProvider('screenProvider')]
    public function testAnAdministratorOpensTheScreen(string $uri): void
    {
        $admin = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);

        $response = $this->handleRequest($uri, cookies: $this->actingAs($admin));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $uri);
    }

    #[DataProvider('screenProvider')]
    public function testAGuestIsSentToTheSignInScreenOfThePanel(string $uri): void
    {
        $response = $this->handleRequest($uri);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode(), $uri);
        self::assertSame('/admin/login', $response->headers->get('Location'), $uri);
    }

    /**
     * The settings change the site as a whole, so opening the panel is not enough for them.
     */
    public function testTheSettingsAskForMoreThanOpeningThePanel(): void
    {
        $admin = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);

        $response = $this->handleRequest('/admin/forum/settings', cookies: $this->actingAs($admin));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testTheOwnerOfTheSiteOpensTheSettings(): void
    {
        $owner = FunctionalUserFactory::createWithPermissions([
            CorePermissions::ADMIN_ACCESS,
            CorePermissions::ADMIN_SETTINGS_MANAGE,
        ]);

        $response = $this->handleRequest('/admin/forum/settings', cookies: $this->actingAs($owner));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * The routes belong to the forum now, so `__()` on these pages resolves against the forum
     * domain — while the chrome around them still comes from the domain of the panel.
     */
    public function testTheScreenIsWrittenInTheTranslationsOfTheForumAndOfThePanel(): void
    {
        $admin = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);

        $response = $this->handleRequest('/admin/forum', cookies: $this->actingAs($admin));

        $body = (string) $response->getContent();

        self::assertStringContainsString('Управление Форумом', $body, 'A string of the forum domain.');
        self::assertStringContainsString('Админ панель', $body, 'The navigation chain of the panel.');
    }

    public function testTheStructureScreenListsTheCategoriesOfTheForum(): void
    {
        $admin = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);
        ForumSection::query()->create([
            'name'         => 'A category of its own',
            'slug'         => 'a-category-of-its-own',
            'parent'       => 0,
            'section_type' => 0,
            'sort'         => 1,
        ]);

        $response = $this->handleRequest('/admin/forum/structure', cookies: $this->actingAs($admin));

        self::assertStringContainsString('A category of its own', (string) $response->getContent());
    }
}
