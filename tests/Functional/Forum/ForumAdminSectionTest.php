<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
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
        yield 'forum' => ['/admin/forum'];
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

        self::assertStringContainsString('Добавить категорию', $body, 'A string of the forum domain.');
        self::assertStringContainsString('Админ панель', $body, 'The navigation chain of the panel.');
    }

    /**
     * The tree is the page itself: every section under its parent, at any depth, with what it
     * holds. A topic nobody can read does not count.
     */
    public function testTheForumScreenShowsEverySectionUnderItsParent(): void
    {
        $admin = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);
        $category = Fixture::insert('forum_sections', ['name' => 'A category of its own', 'slug' => 'a-category', 'parent' => 0, 'section_type' => 0, 'sort' => 1]);
        $folder = Fixture::insert('forum_sections', ['name' => 'A folder inside', 'slug' => 'a-folder', 'parent' => $category, 'section_type' => 0, 'sort' => 1]);
        $section = Fixture::insert('forum_sections', ['name' => 'A section deep down', 'slug' => 'a-section', 'parent' => $folder, 'section_type' => 1, 'sort' => 1, 'access' => 4]);
        Fixture::insert('forum_sections', ['name' => 'A section nobody holds', 'slug' => 'an-orphan', 'parent' => 999999, 'section_type' => 1, 'sort' => 1]);
        Fixture::insert('forum_topic', ['section_id' => $section, 'name' => 'A topic', 'slug' => 'a-topic', 'deleted' => 0]);
        Fixture::insert('forum_topic', ['section_id' => $section, 'name' => 'A hidden topic', 'slug' => 'a-hidden-topic', 'deleted' => 1]);

        $body = (string) $this->handleRequest('/admin/forum', cookies: $this->actingAs($admin))->getContent();

        $positions = array_map(static fn (string $name): int|false => strpos($body, $name), ['A category of its own', 'A folder inside', 'A section deep down']);
        self::assertNotContains(false, $positions, 'Every section is in the tree.');
        self::assertSame($positions, array_values(array_unique($positions)), 'Each name is found once.');
        self::assertTrue($positions[0] < $positions[1] && $positions[1] < $positions[2], 'A section follows its parent.');
        self::assertStringContainsString('--depth: 2', $body, 'The section is two levels deep.');
        self::assertStringContainsString('1 тема', $body, 'The hidden topic is not counted.');
        self::assertStringContainsString('Только для чтения', $body, 'The access of the section is shown.');
        self::assertStringNotContainsString('A section nobody holds', $body, 'A section without a parent is not in the tree.');
    }

    /**
     * The tree took the place of the list of the structure; the old address is gone rather than
     * sent on.
     */
    public function testTheListOfTheStructureHasNoAddressOfItsOwn(): void
    {
        $admin = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);

        $response = $this->handleRequest('/admin/forum/structure', cookies: $this->actingAs($admin));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testANewCategoryLeadsBackToTheTree(): void
    {
        $admin = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);

        $response = $this->postForm('/admin/forum/structure/new', ['name' => 'Fresh category', 'desc' => '', 'section_type' => 0], $admin);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/forum', $response->headers->get('Location'));
        self::assertTrue(ForumSection::query()->where('name', 'Fresh category')->exists());
    }
}
