<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Modules\Admin\Application\Services\CounterRowMapper;
use Johncms\Modules\Admin\Application\UseCases\GetCounterListUseCase;
use Johncms\Modules\Admin\Application\UseCases\ManageCounterUseCase;
use Johncms\Modules\Admin\Application\UseCases\SaveCounterUseCase;
use Johncms\Modules\Admin\Domain\Models\Counter;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;

final readonly class CountersController
{
    private const URL = '/admin/counters';
    private const int NAME_LENGTH = 25;

    /** The first code on the homepage, the second on the other pages. */
    private const int MODE_HOMEPAGE = 1;
    /** The first code on every page. */
    private const int MODE_EVERYWHERE = 2;
    /** The second code on every page. */
    private const int MODE_SECOND_EVERYWHERE = 3;

    public function __construct(
        private NavChain $navChain,
        private GetCounterListUseCase $getList,
        private SaveCounterUseCase $saveCounter,
        private ManageCounterUseCase $manageCounter,
        private CounterRowMapper $rowMapper,
        private Session $session,
    ) {
    }

    public function index(): ViewResponse
    {
        $title = __('Counters');
        $this->navChain->add($title, self::URL);

        return new ViewResponse('@admin/counters.twig', $this->menu($title) + [
            'rows'            => $this->rowMapper->rows($this->getList->execute()),
            'add_url'         => self::URL . '/new',
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }

    public function newForm(): ViewResponse
    {
        return $this->renderForm(null, $this->fieldsOf(null));
    }

    public function editForm(int $id): ViewResponse
    {
        $counter = $this->manageCounter->find($id);
        if ($counter === null) {
            return $this->error(__('Wrong data'));
        }

        return $this->renderForm($counter, $this->fieldsOf($counter));
    }

    public function store(Request $request): ViewResponse
    {
        $id = $request->bodyInt('id') ?: null;
        $counter = $id !== null ? $this->manageCounter->find($id) : null;
        if ($id !== null && $counter === null) {
            return $this->error(__('Wrong data'));
        }

        $fields = [
            'name'                   => mb_substr(trim($request->body('name', '')), 0, self::NAME_LENGTH),
            'link1'                  => trim($request->body('link1', '')),
            'link2'                  => trim($request->body('link2', '')),
            'mode'                   => $this->mode($request->bodyInt('mode', self::MODE_EVERYWHERE)),
            'require_cookie_consent' => $request->hasBody('require_cookie_consent'),
            'enabled'                => $request->hasBody('switch'),
        ];

        $errors = [];
        if ($fields['name'] === '') {
            $errors['name'] = [__('Enter the name of the counter')];
        }
        if ($fields['link1'] === '') {
            $errors['link1'] = [__('Paste the code of the counter')];
        }

        if ($errors !== []) {
            return $this->renderForm($counter, $fields, $errors);
        }

        $this->saveCounter->execute($id, $fields['name'], $fields['link1'], $fields['link2'], $fields['mode'], $fields['require_cookie_consent'], $fields['enabled']);
        $this->session->flash('success_message', $id !== null ? __('The counter is saved') : __('The counter is added'));

        redirect(self::URL);
    }

    public function toggle(Request $request, int $id): ViewResponse
    {
        $enabled = $request->bodyInt('enabled') === 1;
        $this->manageCounter->toggle($id, $enabled);
        $this->session->flash('success_message', $enabled ? __('The counter is turned on') : __('The counter is turned off'));

        redirect(self::URL);
    }

    public function up(int $id): ViewResponse
    {
        $this->manageCounter->moveUp($id);

        redirect(self::URL);
    }

    public function down(int $id): ViewResponse
    {
        $this->manageCounter->moveDown($id);

        redirect(self::URL);
    }

    public function deleteConfirm(Request $request, int $id): ViewResponse
    {
        $counter = $this->manageCounter->find($id);
        if ($counter === null) {
            return $this->error(__('Wrong data'));
        }

        $dialog = [
            'name'        => $counter->name,
            'form_action' => self::URL . '/' . $id . '/delete',
            'back_url'    => self::URL,
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@admin/counter-delete-dialog.twig', $dialog);
        }

        $title = __('Delete counter');
        $this->navChain->add(__('Counters'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/counter-delete-confirm.twig', [
            'title'  => $title,
            'dialog' => $dialog,
        ] + $this->menu($title));
    }

    public function delete(int $id): ViewResponse
    {
        $this->manageCounter->delete($id);
        $this->session->flash('success_message', __('The counter is deleted'));

        redirect(self::URL);
    }

    /**
     * @param array<string, mixed>        $fields
     * @param array<string, list<string>> $errors
     */
    private function renderForm(?Counter $counter, array $fields, array $errors = []): ViewResponse
    {
        $title = $counter !== null ? __('Edit counter') : __('Add counter');
        $this->navChain->add(__('Counters'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/counter-form.twig', $this->menu($title) + [
            'form_action'    => self::URL,
            'id'             => $counter?->id,
            'fields'         => $fields,
            'errors'         => $errors,
            'name_length'    => self::NAME_LENGTH,
            // The legacy mode, the second code everywhere, is offered only to a counter that has it.
            'legacy_mode'    => $counter?->mode === self::MODE_SECOND_EVERYWHERE,
            'banner_enabled' => ! empty(config('johncms')['cookie_banner_enabled']),
            // What is saved, previewed in an isolated frame on the edit page.
            'saved'          => $counter === null ? null : ['link1' => $counter->link1, 'link2' => $counter->link2],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsOf(?Counter $counter): array
    {
        return [
            'name'                   => $counter?->name ?? '',
            'link1'                  => $counter?->link1 ?? '',
            'link2'                  => $counter?->link2 ?? '',
            'mode'                   => $this->mode($counter?->mode ?? self::MODE_EVERYWHERE),
            'require_cookie_consent' => $counter?->require_cookie_consent === 1,
            'enabled'                => $counter === null || $counter->switch === 1,
        ];
    }

    /**
     * One of the three modes the site knows; anything else reads as the first code everywhere.
     */
    private function mode(int $mode): int
    {
        return in_array($mode, [self::MODE_HOMEPAGE, self::MODE_EVERYWHERE, self::MODE_SECOND_EVERYWHERE], true) ? $mode : self::MODE_EVERYWHERE;
    }

    private function error(string $message): ViewResponse
    {
        return new ViewResponse('@admin/pages/result.twig', $this->menu(__('Counters')) + [
            'type'     => 'alert-danger',
            'message'  => $message,
            'back_url' => self::URL,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title): array
    {
        return [
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['counters' => true],
        ];
    }
}
