<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\Controllers;

use Carbon\Carbon;
use Johncms\Http\PageMeta;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Notifications\Application\UseCases\GetNotificationListUseCase;
use Johncms\NavChain;
use Johncms\Notifications\SystemNotifications;
use Johncms\Notifications\Notification;

final readonly class IndexController
{
    public function __construct(
        private NavChain $navChain,
        private Session $session,
        private SystemNotifications $systemNotifications,
        private GetNotificationListUseCase $getNotificationListUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        return $this->render(false, false);
    }

    public function admin(Request $request): ViewResponse
    {
        return $this->render(true, $request->isXmlHttpRequest());
    }

    private function render(bool $admin, bool $offcanvas): ViewResponse
    {
        $this->navChain->add(d__('notifications', 'Notifications'), $admin ? '/admin/notifications' : '/notifications/');

        $pagination = $this->paginationFactory->create($this->getNotificationListUseCase->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->getNotificationListUseCase->getPage($pagination->getPerPage(), $pagination->getOffset());

        $meta = new PageMeta(d__('notifications', 'Notifications'), $pagination->getCurrentPage());

        return new ViewResponse(
            $admin
                ? ($offcanvas ? '@notifications/admin/offcanvas.twig' : '@notifications/admin/index.twig')
                : '@notifications/public/index.twig',
            [
                'title'         => $meta->title,
                'unread_count'  => $admin ? ($this->systemNotifications->counters()['all'] ?? 0) : 0,
                'message'       => $this->session->getFlash('message') ?? '',
                'page_title'    => d__('notifications', 'Notifications'),
                'description'   => $meta->description,
                'notifications' => $result->systemNotifications,
                'items'         => $admin ? $this->adminItems($result->items) : $result->items,
                'total'         => $pagination->getTotal(),
                'pagination'    => $pagination->hasPages() ? $pagination->render() : null,
            ]
        );
    }

    /**
     * The events as the panel shows them: unread ones are marked, and the time is the date
     * the panel prints everywhere.
     *
     * @param list<Notification> $items
     * @return list<array{message: \Twig\Markup, unread: bool, created_at: int, icon: string}>
     */
    private function adminItems(array $items): array
    {
        return array_map(
            static fn (Notification $item): array => [
                'message'    => $item->message,
                // Read before the page marked it: the model still holds the old value.
                'unread'     => $item->getRawOriginal('read_at') === null,
                'created_at' => Carbon::parse($item->getRawOriginal('created_at'))->getTimestamp(),
                'icon'       => match ($item->module) {
                    'forum' => 'message-square',
                    'karma' => 'thumbs-up',
                    default => 'bell',
                },
            ],
            $items,
        );
    }
}
