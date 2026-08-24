<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Access;

use Johncms\Auth\Events\AuthEventType;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\UseCases\GetAuthLogUseCase;
use Johncms\NavChain;

/**
 * The sign-in log: who signed in, what was refused, who changed whose roles.
 *
 * The trail is the point of recording anything at all — an entry nobody can read answers no
 * question — so the screen arrives together with the events it shows.
 */
final readonly class AuthLogController
{
    private const URL = '/admin/auth-log';

    public function __construct(
        private NavChain $navChain,
        private GetAuthLogUseCase $getAuthLog,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $userId = $request->queryInt('user') ?: null;
        $event = $this->event($request);

        $pagination = $this->paginationFactory->create($this->getAuthLog->count($userId, $event));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $rows = $this->getAuthLog->getPage(
            $pagination->getPerPage(),
            $pagination->getOffset(),
            $userId,
            $event
        );

        $title = __('Sign-in log');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $pagination->getCurrentPage());

        return new ViewResponse(
            '@admin/auth-log.twig',
            [
                'title'          => $meta->title,
                'page_title'     => $title,
                'usr_menu'       => ['auth_log' => true],
                'items'          => $rows,
                'total'          => $pagination->getTotal(),
                'per_page'       => $pagination->getPerPage(),
                'pagination'     => $pagination->render(),
                'form_action'    => self::URL,
                'filter_user'    => $userId,
                'filter_event'   => $event,
                'event_options'  => $this->eventOptions(),
            ]
        );
    }

    /**
     * Only a key the core knows is accepted: the filter goes into a query, and an arbitrary
     * string from the address bar has no business reaching it.
     */
    private function event(Request $request): ?string
    {
        $event = trim($request->queryParam('event'));

        return $event === '' ? null : AuthEventType::tryFrom($event)?->value;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function eventOptions(): array
    {
        return array_map(
            static fn (AuthEventType $type): array => ['value' => $type->value, 'label' => $type->label()],
            AuthEventType::cases()
        );
    }
}
