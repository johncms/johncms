<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use FastRoute\RouteCollector;
use Intervention\Image\ImageManager;
use Johncms\Ads;
use Johncms\AdsFactory;
use Johncms\Cache;
use Johncms\Container\PSRContainerFactory;
use Johncms\Counters;
use Johncms\CountersFactory;
use Johncms\Files\FileStorage;
use Johncms\ImageManagerFactory;
use Johncms\Logs\LoggerFactory;
use Johncms\Mail\MailFactory;
use Johncms\Media\MediaEmbed;
use Johncms\NavChain;
use Johncms\Router\RouteCollectorFactory;
use Johncms\Security\Csrf;
use Johncms\Security\HTMLPurifier;
use Johncms\System\Database\PdoFactory;
use Johncms\System\Http\Environment;
use Johncms\System\Http\Request;
use Johncms\System\Http\RequestFactory;
use Johncms\System\i18n\Translator;
use Johncms\System\i18n\TranslatorServiceFactory;
use Johncms\System\Legacy\Bbcode;
use Johncms\System\Legacy\Tools;
use Johncms\System\Users\UserFactory;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Extension\Avatar;
use Johncms\System\View\Render;
use Johncms\System\View\RenderEngineFactory;
use Johncms\System\View\Theme;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\',
        ROOT_PATH . 'system/src'
    )
        ->exclude(
            [
                ROOT_PATH . 'system/src/Counters.php',
                ROOT_PATH . 'system/src/FileInfo.php',
                ROOT_PATH . 'system/src/Config',
                ROOT_PATH . 'system/src/Files',
                ROOT_PATH . 'system/src/Modules',
                ROOT_PATH . 'system/src/Validator',
                ROOT_PATH . 'system/src/Ads.php',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(ContainerInterface::class)->synthetic();
    $services->set(FileStorage::class, FileStorage::class);
    $services->set(LoggerInterface::class)->factory(service(LoggerFactory::class));
    $services->set(Request::class)->factory(service(RequestFactory::class));
    $services->set(\PDO::class, PdoFactory::class)->factory(service(PdoFactory::class));
    $services->set(\Johncms\Users\User::class)->factory(service(\Johncms\Users\UserFactory::class));
    $services->set(\Johncms\System\Users\User::class)->factory(service(UserFactory::class));

    $services->set(Bbcode::class)->factory([Bbcode::class, 'create']);
    $services->set(Tools::class)->factory([Tools::class, 'create']);
    $services->set(NavChain::class)->factory([NavChain::class, 'create']);
    $services->set(ImageManager::class)->factory(service(ImageManagerFactory::class));
    $services->set(Ads::class)->factory(service(AdsFactory::class));
    $services->set(Csrf::class)->factory([Csrf::class, 'create']);
    $services->set('counters', Counters::class)->factory(service(CountersFactory::class));
    $services->set(MailFactory::class)->factory([MailFactory::class, 'create']);
    $services->set(HTMLPurifier::class)->factory([HTMLPurifier::class, 'create']);

    $services->set(Assets::class)->factory([Assets::class, 'create']);
    $services->set(Avatar::class)->factory([Avatar::class, 'create']);
    $services->set(Environment::class)->factory([Environment::class, 'create']);
    $services->set(RouteCollector::class)->factory(service(RouteCollectorFactory::class));
    $services->set(Render::class)->factory(service(RenderEngineFactory::class));
    $services->set(Translator::class)->factory(service(TranslatorServiceFactory::class));
    $services->set(Cache::class)->factory([Cache::class, 'create']);
    $services->set(MediaEmbed::class)->factory([MediaEmbed::class, 'create']);
    $services->set(Theme::class)->factory([Theme::class, 'create']);
};
