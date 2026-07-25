<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\DTO\KarmaSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\ResetKarmaUseCase;
use Johncms\Modules\Admin\Application\UseCases\UpdateKarmaSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class KarmaController
{
    private const URL = '/admin/karma';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private UpdateKarmaSettingsUseCase $updateKarmaSettings,
        private ResetKarmaUseCase $resetKarma,
        private Session $session,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(): string
    {
        return $this->renderForm();
    }

    public function save(): string
    {
        if (! $this->isCsrfValid()) {
            return $this->renderForm(__('Wrong data'));
        }

        $dto = new KarmaSettingsDTO(
            karmaPoints: abs($this->request->bodyInt('karma_points')),
            forumPosts: abs($this->request->bodyInt('forum')),
            enabled: $this->request->hasBody('on'),
            forbidAdmin: $this->request->hasBody('adm'),
        );

        try {
            $this->updateKarmaSettings->execute($dto);
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `system.local.php`'));
        }

        $this->session->flash('success_message', __('Settings are saved successfully'));
        redirect(self::URL);
    }

    public function clearConfirm(): string
    {
        $title = __('Karma');
        $this->navChain->add($title, self::URL);

        $this->render->addData(
            [
                'title'      => $title,
                'page_title' => $title,
                'usr_menu'   => ['karma' => true],
            ]
        );

        return $this->render->render('admin::karma_clean_confirm', [
            'message'     => __('You really want to clear the Karma?'),
            'form_action' => self::URL . '/reset',
            'back_url'    => self::URL,
        ]);
    }

    public function reset(): string
    {
        if ($this->isCsrfValid()) {
            $this->resetKarma->execute();
            $this->session->flash('success_message', __('Karma is cleared'));
        }

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

    private function renderForm(?string $errorMessage = null): string
    {
        $title = __('Karma');
        $this->navChain->add($title);

        $successMessage = $this->session->getFlash('success_message');

        $this->render->addData(
            [
                'title'      => $title,
                'page_title' => $title,
                'usr_menu'   => ['karma' => true],
            ]
        );

        return $this->render->render('admin::karma', [
            'settings'        => config('johncms')['karma'],
            'form_action'     => self::URL,
            'clear_url'       => self::URL . '/clear',
            'error_message'   => $errorMessage,
            'success_message' => $successMessage,
        ]);
    }
}
