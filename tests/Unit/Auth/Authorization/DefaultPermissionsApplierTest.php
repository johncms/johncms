<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\DefaultPermissions;
use Johncms\Auth\Authorization\DefaultPermissionsApplier;
use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Auth\Authorization\RoleSeeder;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use Johncms\Auth\Schema\AuthSchema;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

final class DefaultPermissionsApplierTest extends TestCase
{
    use BootsInMemoryDatabase;

    private EloquentRoleRepository $roles;

    private DefaultPermissionsApplier $applier;

    protected function setUp(): void
    {
        $this->bootDatabase();
        // The built-in roles are named through the gettext helpers, which nothing has registered
        // in an isolated unit test.
        TranslatorFunctions::register(new Translator());
        AuthSchema::create(Capsule::schema());

        $this->roles = new EloquentRoleRepository();
        $this->applier = new DefaultPermissionsApplier($this->roles, $this->defaults());
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    /**
     * The case this exists for: roles created before the code started asking about permissions,
     * carrying none of them.
     */
    public function testARoleWithoutPermissionsGetsTheDefaults(): void
    {
        (new RoleSeeder($this->roles, $this->defaults()))->seed();
        $admin = $this->roles->findBySlug(SystemRole::Admin->value);
        self::assertNotNull($admin);
        $this->roles->setPermissions($admin->id, []);

        $granted = $this->applier->apply();

        self::assertEqualsCanonicalizing(
            $this->defaults()->forRole(SystemRole::Admin->value),
            $this->roles->permissionsFor([$admin->id])
        );
        self::assertArrayHasKey(SystemRole::Admin->value, $granted);
    }

    /**
     * Additive and nothing else: what a site granted on top of the defaults is not a mistake to
     * be corrected.
     */
    public function testWhatTheSiteGrantedItselfSurvives(): void
    {
        (new RoleSeeder($this->roles, $this->defaults()))->seed();
        $moderator = $this->roles->findBySlug(SystemRole::ForumModerator->value);
        self::assertNotNull($moderator);
        $this->roles->setPermissions($moderator->id, ['forum.topic.delete']);

        $this->applier->apply();

        self::assertContains('forum.topic.delete', $this->roles->permissionsFor([$moderator->id]));
        self::assertContains(CorePermissions::ANTIFLOOD_RELAXED, $this->roles->permissionsFor([$moderator->id]));
    }

    public function testRunningAgainGrantsNothing(): void
    {
        (new RoleSeeder($this->roles, $this->defaults()))->seed();

        self::assertSame([], $this->applier->apply());
    }

    /**
     * The defaults say nothing about a role the site added, so neither does this.
     */
    public function testARoleTheSiteAddedIsLeftAlone(): void
    {
        (new RoleSeeder($this->roles, $this->defaults()))->seed();
        $custom = $this->roles->create('news-editor', 'News editor', 20, time());

        $this->applier->apply();

        self::assertSame([], $this->roles->permissionsFor([$custom->id]));
    }

    /**
     * The matrix as the running site assembles it: from the permissions the core and the modules
     * declare, not from a copy kept in the test.
     */
    private function defaults(): DefaultPermissions
    {
        return new DefaultPermissions(new PermissionRegistry([new CorePermissions(), new ForumPermissions()]));
    }
}
