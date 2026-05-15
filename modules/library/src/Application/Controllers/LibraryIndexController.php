<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Application\LegacyRedirectHandler;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Utils;
use Johncms\Modules\Library\Application\Services\ViewHelper;

final readonly class LibraryIndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private LegacyRedirectHandler $legacyRedirectHandler,
        private Tools $tools,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(): string
    {
        $this->legacyRedirectHandler->handle();

        $this->navChain->add(__('Library'), '/library/');

        $this->render->addData([
            'title'      => __('Library'),
            'page_title' => __('Library'),
        ]);

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
                'id'                    => $section->id,
                'name'                  => $this->tools->checkout($section->name),
                'dir'                   => $section->dir,
                'description'           => $section->description ? $this->tools->checkout($section->description) : null,
                'libCounter'            => Utils::libCounter($section->id, $section->dir),
                'sectionListAdminPanel' => ViewHelper::sectionsListAdminPanel(0, $section->id, $i, $total),
            ];
        }

        return $this->render->render('library::main', [
            'total'       => $total,
            'admin'       => $isAdmin,
            'premod'      => $countPremod > 0,
            'countPremod' => $countPremod,
            'new'         => $new,
            'list'        => $list,
        ]);
    }
}
