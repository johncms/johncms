<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Controllers\Admin;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Session;
use Johncms\Modules\Consent\Application\Services\ConsentTitleFormatter;
use Johncms\Modules\Consent\Domain\Models\Consent;
use Johncms\Modules\Consent\Domain\Repository\ConsentRepositoryInterface;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class ConsentListController
{
    private const URL = '/admin/consents';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Session $session,
        private NavChain $navChain,
        private ConsentRepositoryInterface $repository,
        private ConsentTitleFormatter $titleFormatter,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('consent');
    }

    public function __invoke(): ViewResponse
    {
        $title = __('Consents');
        $this->navChain->add($title, self::URL);

        $total = $this->repository->count();
        $pagination = $this->paginationFactory->create($total);

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $successMessage = $this->session->getFlash('success_message');

        $consents = $this->repository->getPage($pagination->getPerPage(), $pagination->getOffset());

        $lngList = config('johncms')['lng_list'] ?? [];

        $items = $consents->map(function (Consent $consent) use ($lngList): array {
            $languageName = $lngList[$consent->language]['name'] ?? $consent->language;

            return [
                'id'            => $consent->id,
                'context'       => $consent->context,
                'language'      => $consent->language,
                'language_name' => $languageName,
                'title'         => $this->titleFormatter->toPlainText($consent->title),
                'version'       => $consent->version,
                'is_required'   => $consent->is_required,
                'is_active'     => $consent->is_active,
                'view_url'      => $consent->hasTextPage() ? '/consent/' . $consent->id : null,
                'edit_url'      => self::URL . '/' . $consent->id . '/edit',
                'delete_url'    => self::URL . '/' . $consent->id . '/delete',
            ];
        })->all();

        return new ViewResponse('@consent/admin/list.twig', [
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['consents' => true],
        ] + [
            'items'           => $items,
            'create_url'      => self::URL . '/create',
            'log_url'         => self::URL . '/log',
            'pagination'      => $pagination->render(),
            'success_message' => $successMessage,
        ]);
    }
}
