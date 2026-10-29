<?php

declare(strict_types=1);

namespace Johncms\Modules\Language\Application\Controllers;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;

final readonly class LanguageController
{
    public function __construct(
        private Session $session,
    ) {
    }

    public function __invoke(Request $request): ViewResponse|string
    {
        if ($request->isPost()) {
            // Remember the language for the rest of the session. The page reloads after the modal
            // is applied, and the locale resolver reads the choice back from here.
            $code = $request->body('setlng');
            $languages = (array) config('johncms.lng_list', []);

            if ($code !== '' && array_key_exists($code, $languages)) {
                $this->session->set('lng', $code);
            }

            return '';
        }

        return new ViewResponse(
            '@language/public/index.twig',
            ['languages' => (array) config('johncms.lng_list', [])]
        );
    }
}
