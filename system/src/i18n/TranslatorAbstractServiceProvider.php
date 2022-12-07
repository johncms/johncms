<?php

declare(strict_types=1);

namespace Johncms\i18n;

use Carbon\Carbon;
use Gettext\TranslatorFunctions;
use Johncms\AbstractServiceProvider;

class TranslatorAbstractServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // Register the system languages domain and folder
        $translator = di(Translator::class);
        $translator->addTranslationDomain('system', __DIR__ . '/../../locale');
        $translator->defaultDomain('system');
        // Register language helpers
        TranslatorFunctions::register($translator);
        Carbon::setLocale($translator->getLocale());
    }
}
