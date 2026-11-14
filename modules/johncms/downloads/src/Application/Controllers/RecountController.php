<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Request;
use Johncms\Modules\Downloads\Application\UseCases\RecountCategoriesUseCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class RecountController
{
    public function __construct(
        private RecountCategoriesUseCase $recount,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $id = max(0, $request->bodyInt('id'));

        $this->recount->execute();

        return new RedirectResponse('/downloads/' . ($id ? '?id=' . $id : ''));
    }
}
