<?php

declare(strict_types=1);

namespace Johncms\Container;

use Johncms\Auth\Authentication\AuthenticatorInterface;
use Johncms\Auth\Authorization\AccessVoterInterface;
use Johncms\Auth\External\ExternalIdentityProviderInterface;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Captcha\CaptchaProviderInterface;
use Johncms\Content\Embed\EmbedProviderInterface;
use Johncms\Content\Transformer\ContentTransformerInterface;
use Johncms\Database\Migrations\MigrationSourceProviderInterface;
use Johncms\Security\HtmlPolicyProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class PSRContainerFactory
{
    private static ?ContainerInterface $containerInstance = null;

    public function __invoke(): ContainerInterface
    {
        $cachePath = CACHE_PATH . 'container.php';
        if (CACHE_CONTAINER && file_exists($cachePath)) {
            require_once $cachePath;
            $container = new \ProjectServiceContainer();

            $container->set(PsrContainerInterface::class, $container);

            self::$containerInstance = $container;

            return $container;
        }

        $container = new ContainerBuilder();

        $this->registerExtensionPoints($container);
        $this->loadCoreServices($container);
        $this->loadModuleServices($container);
        $this->loadOverrideServices($container);

        $container->compile();

        if (CACHE_CONTAINER) {
            $dumper = new PhpDumper($container);
            file_put_contents(
                $cachePath,
                $dumper->dump(
                    [
                        'class'    => 'ProjectServiceContainer',
                        'as_files' => false,
                    ]
                )
            );
        }

        self::$containerInstance = $container;

        return $container;
    }

    public static function getContainer(): ContainerInterface
    {
        if (self::$containerInstance === null) {
            (new self())();
        }
        return self::$containerInstance;
    }

    /**
     * The extension points a module joins by implementing an interface: a way of identifying the
     * visitor, a rule about what is allowed, the permissions a module declares, the HTML policy
     * its own kind of content is cleaned by, the way visitors are told from bots, the two halves
     * of the content pipeline — a step that edits a rendered text and a media site whose links
     * become players — and a directory of migrations the database is taken through.
     *
     * Registered on the builder rather than as an instanceof rule of a services file, because
     * such a rule only reaches the services declared in that same file. A module would have to
     * repeat it in its own services.php, and one that forgot would compile fine and simply never
     * be asked anything — a voter that never votes and permissions that never reach the editor.
     */
    private function registerExtensionPoints(ContainerBuilder $container): void
    {
        $tags = [
            AuthenticatorInterface::class      => 'johncms.auth.authenticator',
            AccessVoterInterface::class        => 'johncms.auth.voter',
            PermissionProviderInterface::class => 'johncms.auth.permissions',
            ExternalIdentityProviderInterface::class => 'johncms.auth.external_provider',
            HtmlPolicyProviderInterface::class  => 'johncms.html_policy_provider',
            CaptchaProviderInterface::class     => 'johncms.captcha_provider',
            ContentTransformerInterface::class  => 'johncms.content_transformer',
            EmbedProviderInterface::class       => 'johncms.embed_provider',
            MigrationSourceProviderInterface::class => 'johncms.migration_source',
        ];

        foreach ($tags as $interface => $tag) {
            $container->registerForAutoconfiguration($interface)->addTag($tag);
        }
    }

    private function loadCoreServices(ContainerBuilder $container): void
    {
        $container->set(PsrContainerInterface::class, $container);
        $loader = new PhpFileLoader(
            $container,
            new FileLocator(ROOT_PATH . 'system/config')
        );

        $loader->load('services.php');
    }

    private function loadModuleServices(ContainerBuilder $container): void
    {
        foreach (glob(MODULES_PATH . '*/config/services.php') as $file) {
            $loader = new PhpFileLoader(
                $container,
                new FileLocator(\dirname($file))
            );

            $loader->load('services.php');
        }
    }

    private function loadOverrideServices(ContainerBuilder $container): void
    {
        if (! is_file(CONFIG_PATH . 'services.local.php')) {
            return;
        }

        $loader = new PhpFileLoader(
            $container,
            new FileLocator(CONFIG_PATH)
        );

        $loader->load('services.local.php');
    }
}
