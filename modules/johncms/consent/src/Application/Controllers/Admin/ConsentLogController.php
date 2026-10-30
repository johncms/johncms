<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Controllers\Admin;

use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Consent\Application\Services\ConsentTitleFormatter;
use Johncms\Modules\Consent\Domain\Models\ConsentLog;
use Johncms\Modules\Consent\Domain\Repository\ConsentLogRepositoryInterface;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Utils\DateFormatterInterface;

final readonly class ConsentLogController
{
    private const URL = '/admin/consents/log';

    public function __construct(
        private NavChain $navChain,
        private ConsentLogRepositoryInterface $repository,
        private ConsentTitleFormatter $titleFormatter,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private DateFormatterInterface $dateFormatter,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $title = __('Consent log');
        $this->navChain->add(__('Consents'), '/admin/consents');
        $this->navChain->add($title, self::URL);

        $total = $this->repository->count();
        $pagination = $this->paginationFactory->create($total);

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $entries = $this->repository->getPage($pagination->getPerPage(), $pagination->getOffset());
        $names = $this->repository->namesOf(
            array_values(array_unique(array_filter($entries->pluck('user_id')->all())))
        );

        $items = $entries->map(fn (ConsentLog $entry): array => [
            'id'            => $entry->id,
            'user_id'       => $entry->user_id,
            'user_name'     => $names[$entry->user_id] ?? null,
            'consent_id'    => $entry->consent_id,
            'consent_title' => $entry->consent !== null ? $this->titleFormatter->toPlainText($entry->consent->title) : null,
            'context'       => $entry->consent?->context,
            'version'       => $entry->version,
            'ip_address'    => $entry->ip_address,
            'accepted_at'   => $entry->accepted_at === null ? '' : $this->dateFormatter->format($entry->accepted_at->getTimestamp()),
        ])->all();

        return new ViewResponse('@consent/admin/log.twig', [
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['consents' => true],
        ] + [
            'items'      => $items,
            'back_url'   => '/admin/consents',
            'total'      => $total,
            'per_page'   => $pagination->getPerPage(),
            'pagination' => $pagination->render(),
        ]);
    }
}
