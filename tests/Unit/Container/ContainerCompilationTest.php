<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Auth\Authorization\Voters\BanVoter;
use Johncms\Auth\CurrentUser;
use Johncms\Container\PSRContainerFactory;
use Johncms\Http\CookieQueue;
use Johncms\Modules\Admin\Application\Services\AdminPermissions;
use Johncms\Modules\Consent\Application\View\CookieBannerExtension;
use Johncms\Modules\Forum\Application\Console\CleanupOrphanFilesCommand;
use Johncms\Modules\News\Application\Sitemap\NewsUrlsProvider;
use Johncms\View\Twig\Extension\MailExtension;
use Johncms\Http\Environment;
use Johncms\Http\Session;
use Johncms\NavChain;
use Johncms\View\Menu\MenuRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use ReflectionClass;

/**
 * Guards the container definitions themselves.
 *
 * Every class under system/src and the module src directories is autowired by a directory-wide
 * load(), so a new class with a scalar constructor argument (an exception, a DTO) breaks the
 * compilation of the whole container and takes the site down. Nothing else in the gate catches
 * that: the code is valid PHP, passes the coding standard, static analysis and every other test.
 *
 * Relies on CACHE_CONTAINER being false (config/constants.php): with a dumped container the
 * factory would return the cached one and nothing would be compiled.
 *
 * Stays in the unit suite on purpose: it needs no database, and CI runs `composer test:unit`, so
 * this is the only place where a broken container definition fails the pipeline.
 */
final class ContainerCompilationTest extends TestCase
{
    private ?ContainerInterface $previousInstance = null;

    protected function setUp(): void
    {
        $this->previousInstance = $this->containerInstanceProperty()->getValue();
    }

    protected function tearDown(): void
    {
        // The factory writes the built container into a private static property. Restore it,
        // otherwise this test would leave a fully built container behind for every later test.
        $this->containerInstanceProperty()->setValue(null, $this->previousInstance);
    }

    public function testTheServiceContainerCompiles(): void
    {
        $container = (new PSRContainerFactory())();

        self::assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testTheResettableServicesAreTheRequestScopedOnesOnly(): void
    {
        // The kernel resets every service tagged johncms.resettable before it serves a request,
        // and iterating that tag builds all of them. The tag comes from an instanceof rule on
        // ResetInterface, which Symfony's console application implements too — tagging it would
        // build the whole CLI application, commands included, on every HTTP request. Hence its
        // definition sits before that rule in services.php, which this test guards.
        $container = (new PSRContainerFactory())();
        self::assertInstanceOf(ContainerBuilder::class, $container);

        $tagged = array_keys($container->findTaggedServiceIds('johncms.resettable'));
        sort($tagged);

        self::assertSame(
            [
                StaffTitles::class,
                BanVoter::class,
                CurrentUser::class,
                CookieQueue::class,
                Environment::class,
                Session::class,
                NavChain::class,
                // Holds the menu items this visitor may open, which is a fact about one request.
                MenuRegistry::class,
            ],
            $tagged
        );
    }

    /**
     * A module joins an extension point by implementing an interface, and the tag is applied by
     * registerForAutoconfiguration() in the factory. As an instanceof rule of the core services
     * file it reached the core alone: a permission declared by a module compiled fine and never
     * appeared in the role editor, which is exactly what happened once.
     */
    public function testAPermissionDeclaredByAModuleIsCollectedToo(): void
    {
        $container = (new PSRContainerFactory())();
        self::assertInstanceOf(ContainerBuilder::class, $container);

        $providers = array_keys($container->findTaggedServiceIds('johncms.auth.permissions'));

        self::assertContains(CorePermissions::class, $providers);
        self::assertContains(AdminPermissions::class, $providers);
    }

    /**
     * The same tag applied twice — once by an instanceof rule, once by autoconfiguration — would
     * put the service into its tagged iterator twice, and every voter would vote twice.
     */
    public function testTheExtensionPointsAreTaggedOnce(): void
    {
        $container = (new PSRContainerFactory())();
        self::assertInstanceOf(ContainerBuilder::class, $container);

        $tags = [
            'johncms.auth.voter',
            'johncms.auth.permissions',
            'johncms.auth.authenticator',
            'johncms.console_command',
            'johncms.sitemap_provider',
            'johncms.twig_extension',
            'johncms.resettable',
            'johncms.validator.rule_factory',
        ];

        foreach ($tags as $tag) {
            foreach ($container->findTaggedServiceIds($tag) as $id => $attributes) {
                self::assertCount(1, $attributes, $id . ' carries ' . $tag . ' more than once');
            }
        }
    }

    /**
     * A console command and a group of sitemap addresses are extension points like a permission:
     * the module implements the interface and the tag is applied by autoconfiguration. Before
     * that, every module shipping a command repeated an instanceof rule in its own services.php —
     * five of the six that carried it had no command at all, and the one that did would have lost
     * its command the day somebody tidied the line away.
     */
    public function testACommandAndASitemapProviderDeclaredByAModuleAreCollectedToo(): void
    {
        $container = (new PSRContainerFactory())();
        self::assertInstanceOf(ContainerBuilder::class, $container);

        self::assertContains(
            CleanupOrphanFilesCommand::class,
            array_keys($container->findTaggedServiceIds('johncms.console_command'))
        );
        self::assertContains(
            NewsUrlsProvider::class,
            array_keys($container->findTaggedServiceIds('johncms.sitemap_provider'))
        );
    }

    /**
     * Twig has three environments here, and autoconfiguration knows only the web one: every
     * extension is tagged for it, and what belongs to mail or to the installer says so by hand.
     * MailExtension belongs to mail alone — a message is read outside the site and prints
     * absolute addresses — so it opts out of autoconfiguration, and this is what says it stayed
     * out.
     */
    public function testTheMailTwigExtensionStaysOutOfTheWebEnvironment(): void
    {
        $container = (new PSRContainerFactory())();
        self::assertInstanceOf(ContainerBuilder::class, $container);

        $web = array_keys($container->findTaggedServiceIds('johncms.twig_extension'));

        self::assertNotContains(MailExtension::class, $web);
        self::assertContains(
            MailExtension::class,
            array_keys($container->findTaggedServiceIds('johncms.twig_extension.mail'))
        );
        // And the other direction: an extension a module ships reaches the pages without the
        // module tagging it.
        self::assertContains(CookieBannerExtension::class, $web);
    }

    private function containerInstanceProperty(): \ReflectionProperty
    {
        return (new ReflectionClass(PSRContainerFactory::class))->getProperty('containerInstance');
    }
}
