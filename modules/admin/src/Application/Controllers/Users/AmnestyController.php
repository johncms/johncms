<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Modules\Admin\Application\UseCases\ApplyAmnestyUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Validator\Validator;

final readonly class AmnestyController
{
    private const URL = '/admin/bans/amnesty';

    public function __construct(
        private NavChain $navChain,
        private ApplyAmnestyUseCase $applyAmnesty,
        private Session $session,
    ) {
    }

    public function form(): ViewResponse
    {
        return $this->renderForm();
    }

    public function apply(Request $request): ViewResponse
    {
        if (! $this->isCsrfValid($request)) {
            return $this->renderForm(__('Wrong data'));
        }

        $clearDatabase = $request->bodyInt('term') === 1;
        $this->applyAmnesty->execute($clearDatabase);

        $this->session->flash('success_message', $clearDatabase
            ? __('Amnesty has been successful')
            : __('All the users with active bans were unbanned (Except for bans &quot;till cancel&quot;)'));
        redirect('/admin/bans');
    }

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderForm(?string $errorMessage = null): ViewResponse
    {
        $title = __('Amnesty');
        $this->navChain->add(__('Ban Panel'), '/admin/bans');
        $this->navChain->add($title);

        return new ViewResponse(
            '@admin/amnesty.twig',
            [
                'title'         => $title,
                'page_title'    => $title,
                'usr_menu'      => ['ban_panel' => true],
                'form_action'   => self::URL,
                'error_message' => $errorMessage,
            ]
        );
    }
}
