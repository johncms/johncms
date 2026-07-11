<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Controllers\Admin;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Consent\Domain\Models\Consent;
use Johncms\Modules\Consent\Domain\Repository\ConsentRepositoryInterface;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class ConsentListController
{
    private const URL = '/admin/consents';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private ConsentRepositoryInterface $repository,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('consent');
    }

    public function __invoke(): string
    {
        $title = __('Consents');
        $this->navChain->add($title, self::URL);
        $this->render->addData([
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['consents' => true],
        ]);

        $total = $this->repository->count();
        $pagination = $this->paginationFactory->create($total);

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $successMessage = null;
        if (! empty($_SESSION['success_message'])) {
            $successMessage = (string) $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        $consents = $this->repository->getPage($pagination->getPerPage(), $pagination->getOffset());

        $lngList = config('johncms')['lng_list'] ?? [];

        $items = $consents->map(static function (Consent $consent) use ($lngList): array {
            $languageName = $lngList[$consent->language]['name'] ?? $consent->language;

            return [
                'id'            => $consent->id,
                'context'       => $consent->context,
                'language'      => $consent->language,
                'language_name' => $languageName,
                'title'         => $consent->title,
                'version'       => $consent->version,
                'is_required'   => $consent->is_required,
                'is_active'     => $consent->is_active,
                'view_url'      => '/consent/' . $consent->id,
                'edit_url'      => self::URL . '/' . $consent->id . '/edit',
                'delete_url'    => self::URL . '/' . $consent->id . '/delete',
            ];
        })->all();

        return $this->render->render('consent::admin/list', [
            'items'           => $items,
            'create_url'      => self::URL . '/create',
            'log_url'         => self::URL . '/log',
            'pagination'      => $pagination->render(),
            'success_message' => $successMessage,
        ]);
    }
}
