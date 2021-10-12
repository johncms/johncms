<?php

namespace Johncms\Install;

use Gettext\TranslatorFunctions;
use Johncms\Container\ContainerFactory;
use Johncms\Http\Session;
use Johncms\i18n\Translator;
use Johncms\View\Extension\Assets;
use Johncms\View\Render;
use Psr\Container\ContainerInterface;

class Install
{
    protected ContainerInterface $container;

    public function __construct()
    {
        $this->container = ContainerFactory::getContainer();
    }

    public function init()
    {
        $this->setupTranslator();
        $this->setupRender();
    }

    private function setupTranslator(): void
    {
        $session = $this->container->get(Session::class);
        $translator = new Translator();
        $translator->setLocale($session->get('lng', 'en'));
        $translator->addTranslationDomain('install', ROOT_PATH . 'install/locale');
        $translator->defaultDomain('install');
        TranslatorFunctions::register($translator);
        $this->container->instance(Translator::class, $translator);
    }

    private function setupRender(): void
    {
        $translator = $this->container->get(Translator::class);
        $assets = $this->container->get(Assets::class);

        $view = new Render('phtml');
        $view->setTheme('default');
        $view->addFolder('system', realpath(THEMES_PATH . 'default/templates/system'));
        $view->loadExtension($assets);
        $view->addData(
            [
                'locale' => $translator->getLocale(),
            ]
        );
        $view->addFolder('install', ROOT_PATH . 'install/templates/');
        $this->container->instance(Render::class, $view);
    }
}
