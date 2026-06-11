<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Help\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Help\Application\UseCases\GetUserSmiliesUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class UserSmiliesController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private GetUserSmiliesUseCase $userSmilies,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('help');
    }

    public function __invoke(string $cat): string
    {
        if (! $this->userSmilies->isValidCategory($cat)) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'         => __('Wrong data'),
                'type'          => 'alert-danger',
                'message'       => __('The directory does not exist'),
                'back_url'      => '/help/smilies/',
                'back_url_name' => __('Back'),
            ]);
        }

        $title = $this->userSmilies->categoryTitle($cat);

        $this->navChain->add(__('Information, FAQ'), '/help/');
        $this->navChain->add(__('Smiles'), '/help/smilies/');
        $this->navChain->add($title);

        $pagination = $this->paginationFactory->create($this->userSmilies->count($cat));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $meta = new PageMeta($title . ' — ' . __('Smiles'), $pagination->getCurrentPage());
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $title,
            'description' => $meta->description,
        ]);

        $total = $pagination->getTotal();
        $data = [
            'items'    => [],
            'total'    => $total,
            'back_url' => '/help/smilies/',
        ];

        if ($total > 0) {
            if ($this->currentUser->isValid()) {
                $data['user_smiles_current'] = $this->userSmilies->userSmiliesCount();
                $data['user_smiles_max'] = GetUserSmiliesUseCase::USER_SMILIES_MAX;
            }

            $data['items'] = $this->userSmilies->getPage($cat, $pagination->getPerPage(), $pagination->getOffset());
            $data['pagination'] = $pagination->render();
            $data['form_action'] = '/help/smilies/set/?cat=' . urlencode($cat) . '&page=' . $pagination->getCurrentPage();
        }

        return $this->render->render('help::smiles_list', ['data' => $data]);
    }
}
