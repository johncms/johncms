<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\UseCases\ApplyAmnestyUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class AmnestyController
{
    private const URL = '/admin/bans/amnesty';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private ApplyAmnestyUseCase $applyAmnesty,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function form(): string
    {
        return $this->renderForm();
    }

    public function apply(): string
    {
        if (! $this->isCsrfValid()) {
            return $this->renderForm(__('Wrong data'));
        }

        $clearDatabase = (int) $this->request->getPost('term', 0, FILTER_VALIDATE_INT) === 1;
        $this->applyAmnesty->execute($clearDatabase);

        $_SESSION['success_message'] = $clearDatabase
            ? __('Amnesty has been successful')
            : __('All the users with active bans were unbanned (Except for bans &quot;till cancel&quot;)');
        redirect('/admin/bans');
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderForm(?string $errorMessage = null): string
    {
        $title = __('Amnesty');
        $this->navChain->add(__('Ban Panel'), '/admin/bans');
        $this->navChain->add($title);

        $this->render->addData(
            [
                'title'      => $title,
                'page_title' => $title,
                'usr_menu'   => ['ban_panel' => true],
            ]
        );

        return $this->render->render(
            'admin::amnesty',
            [
                'form_action'   => self::URL,
                'error_message' => $errorMessage,
            ]
        );
    }
}
