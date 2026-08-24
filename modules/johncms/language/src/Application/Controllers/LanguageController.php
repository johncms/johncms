<?php

declare(strict_types=1);

namespace Johncms\Modules\Language\Application\Controllers;

use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;

final readonly class LanguageController
{
    public function __invoke(Request $request): ViewResponse|string
    {
        if ($request->getMethod() === 'POST') {
            return '';
        }

        return new ViewResponse(
            '@language/public/index.twig',
            ['languages' => (array) config('johncms.lng_list', [])]
        );
    }
}
