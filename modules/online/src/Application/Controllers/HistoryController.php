<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Forum\Application\Services\ForumVisitorPlaceFormatter;
use Johncms\Modules\Online\Application\FiltersBuilder;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\i18n\Translator;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Throwable;

final readonly class HistoryController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private Translator $translator,
        private Request $request,
        private FiltersBuilder $filtersBuilder,
    ) {
        $this->controllerContext->initModule('online');
    }

    public function __invoke(): string
    {
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $pageTitle = __('History');
        $meta = new PageMeta($pageTitle . ' — ' . __('Online'), $page);

        $this->navChain->add(__('Online'), '/online/');

        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        $forumPlaceFormatter = null;
        try {
            $forumPlaceFormatter = di(ForumVisitorPlaceFormatter::class);
            $this->translator->addTranslationDomain('forum', MODULES_PATH . 'forum/locale', false);
        } catch (Throwable) {
        }

        $filters = $this->filtersBuilder->build('history');

        $users = User::query()
            ->whereBetween('lastdate', [(time() - 172800), (time() - 310)])
            ->orderBy('lastdate', 'desc')
            ->paginate($this->currentUser->config->kmess);

        $total = $users->total();
        $items = [];

        if ($total) {
            $items = $users->getItems()->map(
                function ($item) use ($forumPlaceFormatter) {
                    /** @var User $item */
                    $place = (string) $item->place;
                    $item->place_name = $forumPlaceFormatter !== null && str_starts_with($place, '/forum')
                        ? $forumPlaceFormatter->format($place)
                        : $this->tools->displayPlace($place);
                    $item->display_date = $this->tools->displayDate($item->sestime);
                    return $item;
                }
            );
        }

        return $this->render->render('online::users', [
            'data' => [
                'filters'    => $filters,
                'pagination' => $users->render(),
                'total'      => $total,
                'items'      => $items,
            ],
        ]);
    }
}
