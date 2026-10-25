<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\Services\BanListControlsMapper;
use Johncms\Modules\Admin\Application\UseCases\ApplyAmnestyUseCase;
use Johncms\NavChain;

/**
 * The amnesty. The choice is a dialog: the modal of the panel asks for it with X-Requested-With
 * and gets the dialog alone, a plain visit gets it as a page.
 */
final readonly class AmnestyController
{
    private const URL = '/admin/bans/amnesty';

    public function __construct(
        private NavChain $navChain,
        private ApplyAmnestyUseCase $applyAmnesty,
        private Session $session,
    ) {
    }

    public function form(Request $request): ViewResponse
    {
        $data = [
            'form_action' => self::URL,
            'back_url'    => BanListControlsMapper::PATH,
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@admin/bans/amnesty-dialog.twig', $data);
        }

        $title = __('Amnesty');
        $this->navChain->add(__('Ban Panel'), BanListControlsMapper::PATH);
        $this->navChain->add($title);

        return new ViewResponse('@admin/amnesty.twig', [
            // The dialog carries the heading itself.
            'title'    => $title,
            'usr_menu' => ['ban_panel' => true],
            'dialog'   => $data,
        ]);
    }

    public function apply(Request $request): ViewResponse
    {
        $clearDatabase = $request->bodyInt('term') === 1;
        $this->applyAmnesty->execute($clearDatabase);

        $this->session->flash('success_message', $clearDatabase
            ? __('Amnesty has been successful')
            : __('All the users with active bans were unbanned (Except for bans &quot;till cancel&quot;)'));
        redirect(BanListControlsMapper::PATH);
    }
}
