<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Modules\Admin\Application\DTO\KarmaSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\ResetKarmaUseCase;
use Johncms\Modules\Admin\Application\UseCases\UpdateKarmaSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;

/**
 * The settings of the karma and clearing it. Clearing is confirmed in a dialog: the modal of the
 * panel asks for it with X-Requested-With and gets the dialog alone, a plain visit gets it as a
 * page.
 */
final readonly class KarmaController
{
    private const URL = '/admin/karma';

    public function __construct(
        private NavChain $navChain,
        private UpdateKarmaSettingsUseCase $updateKarmaSettings,
        private ResetKarmaUseCase $resetKarma,
        private KarmaRepositoryInterface $karma,
        private Session $session,
    ) {
    }

    public function index(): ViewResponse
    {
        return $this->renderForm();
    }

    public function save(Request $request): ViewResponse
    {
        $dto = new KarmaSettingsDTO(
            karmaPoints: abs($request->bodyInt('karma_points')),
            forumPosts: abs($request->bodyInt('forum')),
            enabled: $request->hasBody('on'),
            staffVotable: $request->hasBody('adm'),
        );

        try {
            $this->updateKarmaSettings->execute($dto);
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `system.local.php`'));
        }

        $this->session->flash('success_message', __('Settings are saved successfully'));
        redirect(self::URL);
    }

    public function clearConfirm(Request $request): ViewResponse
    {
        $data = [
            'votes'       => $this->karma->countVotes(),
            'form_action' => self::URL . '/reset',
            'back_url'    => self::URL,
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@admin/karma/clear-dialog.twig', $data);
        }

        $title = __('Clear Karma');
        $this->navChain->add(__('Karma'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/karma-clear-confirm.twig', [
            // The dialog carries the heading itself.
            'title'    => $title,
            'usr_menu' => ['karma' => true],
            'dialog'   => $data,
        ]);
    }

    public function reset(): ViewResponse
    {
        $this->resetKarma->execute();
        $this->session->flash('success_message', __('Karma is cleared'));

        redirect(self::URL);
    }

    private function renderForm(string $errorMessage = ''): ViewResponse
    {
        $title = __('Karma');
        $this->navChain->add($title);

        return new ViewResponse('@admin/karma.twig', [
            'title'           => $title,
            'page_title'      => $title,
            'usr_menu'        => ['karma' => true],
            'settings'        => (array) config('johncms.karma', []),
            'form_action'     => self::URL,
            'clear_url'       => self::URL . '/clear',
            'error_message'   => $errorMessage,
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }
}
