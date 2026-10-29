<?php

declare(strict_types=1);

namespace Johncms\Modules\Language\Application\Controllers;

use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\System\i18n\LocaleResolver;

final readonly class LanguageController
{
    public function __construct(
        private LocaleResolver $localeResolver,
    ) {
    }

    public function __invoke(Request $request): ViewResponse|string
    {
        if ($request->isPost()) {
            // The page reloads after the modal is applied and resolves to the remembered language.
            $this->localeResolver->remember($request->body('setlng'));

            return '';
        }

        return new ViewResponse(
            '@language/public/index.twig',
            ['languages' => (array) config('johncms.lng_list', [])]
        );
    }
}
