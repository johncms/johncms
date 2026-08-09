<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Library\Application\LegacyRedirectHandler;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Utils;

final readonly class LibraryIndexController
{
    public function __construct(
        private NavChain $navChain,
        private LegacyRedirectHandler $legacyRedirectHandler,
        private User $currentUser,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $this->legacyRedirectHandler->handle($request);

        $this->navChain->add(__('Library'), '/library/');

        $isAdmin     = $this->currentUser->rights > 4;
        $countPremod = $isAdmin ? LibraryText::query()->where('premod', 0)->count() : 0;
        $new         = LibraryText::query()->where('time', '>', time() - 259200)->where('premod', 1)->count();

        $sections = LibraryCategory::query()->where('parent', 0)->orderBy('pos')->get();
        $total    = $sections->count();

        $list = [];
        $i    = 0;
        foreach ($sections as $section) {
            $i++;
            $list[] = [
                'id'          => $section->id,
                'url'         => $section->url,
                'name'        => $section->name,
                'description' => $section->description,
                'counter'     => Utils::libCounter($section->id, $section->dir),
                'position'    => $i,
            ];
        }

        return new ViewResponse('@library/public/index.twig', [
            'title'        => __('Library'),
            'page_title'   => __('Library'),
            'library_open' => (bool) config('johncms.mod_lib'),
            'total'        => $total,
            'sections'     => $list,
            'admin'        => $isAdmin,
            'premod_count' => $countPremod,
            'new_articles' => $new,
        ]);
    }
}
