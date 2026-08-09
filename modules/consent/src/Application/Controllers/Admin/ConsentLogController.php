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

final readonly class ConsentLogController
{
    private const URL = '/admin/consents/log';

    public function __construct(
        private NavChain $navChain,
        private ConsentLogRepositoryInterface $repository,
        private ConsentTitleFormatter $titleFormatter,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
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

        $items = $entries->map(fn (ConsentLog $entry): array => [
            'id'            => $entry->id,
            'user_id'       => $entry->user_id,
            'consent_id'    => $entry->consent_id,
            'consent_title' => $entry->consent !== null ? $this->titleFormatter->toPlainText($entry->consent->title) : null,
            'version'       => $entry->version,
            'ip_address'    => $entry->ip_address,
            'accepted_at'   => $entry->accepted_at?->format('Y-m-d H:i:s'),
        ])->all();

        return new ViewResponse('@consent/admin/log.twig', [
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['consents' => true],
        ] + [
            'items'      => $items,
            'back_url'   => '/admin/consents',
            'pagination' => $pagination->render(),
        ]);
    }
}
