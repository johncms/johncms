<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\System;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\UseCases\RebuildSmiliesCacheUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\SmiliesCacheWriteException;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class EmoticonsController
{
    private const URL = '/admin/emoticons';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private RebuildSmiliesCacheUseCase $rebuildSmiliesCacheUseCase,
        private Session $session,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(): string
    {
        return $this->renderPage();
    }

    public function rebuild(): string
    {
        if (! $this->isCsrfValid()) {
            return $this->renderPage(__('Wrong data'));
        }

        try {
            $total = $this->rebuildSmiliesCacheUseCase->execute();
        } catch (SmiliesCacheWriteException) {
            return $this->renderPage(__('Error updating cache'));
        }

        $this->session->flash('success_message', __('Smilie cache updated successfully') . ': ' . $total);
        redirect(self::URL);
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => $this->request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderPage(?string $errorMessage = null): string
    {
        $title = __('Smilies');
        $this->navChain->add($title);

        $successMessage = $this->session->getFlash('success_message');

        $this->render->addData(
            [
                'title'      => $title,
                'page_title' => $title,
                'sys_menu'   => ['emoticons' => true],
            ]
        );

        return $this->render->render(
            'admin::emoticons',
            [
                'form_action'     => self::URL,
                'error_message'   => $errorMessage,
                'success_message' => $successMessage,
            ]
        );
    }
}
