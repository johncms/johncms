<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\UseCases\GetCounterListUseCase;
use Johncms\Modules\Admin\Application\UseCases\ManageCounterUseCase;
use Johncms\Modules\Admin\Application\UseCases\SaveCounterUseCase;
use Johncms\Modules\Admin\Domain\Models\Counter;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class CountersController
{
    private const URL = '/admin/counters';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private GetCounterListUseCase $getList,
        private SaveCounterUseCase $saveCounter,
        private ManageCounterUseCase $manageCounter,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(): string
    {
        $title = __('Counters');
        $this->navChain->add($title, self::URL);
        $this->render->addData($this->menu($title));

        return $this->render->render('admin::counters', [
            'items'   => $this->getList->execute(),
            'add_url' => self::URL . '/new',
        ]);
    }

    public function view(int $id): string
    {
        $counter = $this->manageCounter->find($id);
        if ($counter === null) {
            return $this->error(__('Wrong data'));
        }

        $title = __('Viewing');
        $this->navChain->add(__('Counters'), self::URL);
        $this->navChain->add($title);
        $this->render->addData($this->menu($title));

        return $this->render->render('admin::counters_view', [
            'id'                     => $counter->id,
            'name'                   => $counter->name,
            'counter_1'              => $counter->link1,
            'counter_2'              => $counter->link2,
            'mode_name'              => $this->modeName($counter->mode),
            'enabled'                => $counter->switch === 1,
            'require_cookie_consent' => $counter->require_cookie_consent === 1,
            'base_url'               => self::URL,
        ]);
    }

    public function newForm(): string
    {
        return $this->renderForm(null);
    }

    public function editForm(int $id): string
    {
        $counter = $this->manageCounter->find($id);
        if ($counter === null) {
            return $this->error(__('Wrong data'));
        }

        return $this->renderForm($counter);
    }

    public function preview(Request $request): string
    {
        if (! $this->isCsrfValid($request)) {
            return $this->error(__('Wrong data'));
        }

        $id = $request->bodyInt('id');
        $name = mb_substr(trim($request->body('name', '')), 0, 25);
        $link1 = trim($request->body('link1', ''));
        $link2 = trim($request->body('link2', ''));
        $mode = $request->bodyInt('mode', 1);
        $requireCookieConsent = $request->hasBody('require_cookie_consent');
        $enabled = $request->hasBody('switch');

        if ($name === '' || $link1 === '') {
            return $this->error(__('The required fields are not filled'));
        }

        $title = __('Counters');
        $this->navChain->add($title, self::URL);
        $this->render->addData($this->menu($title));

        return $this->render->render('admin::counters_add_confirm', [
            'form_action'            => self::URL,
            'name'                   => $name,
            'counter_1'              => $link1,
            'counter_2'              => $link2,
            'mode'                   => $mode,
            'require_cookie_consent' => $requireCookieConsent,
            'enabled'                => $enabled,
            'id'                     => $id ?: null,
        ]);
    }

    public function store(Request $request): string
    {
        if (! $this->isCsrfValid($request)) {
            return $this->error(__('Wrong data'));
        }

        $id = $request->bodyInt('id');
        $name = mb_substr(trim($request->body('name', '')), 0, 25);
        $link1 = trim($request->body('link1', ''));
        $link2 = trim($request->body('link2', ''));
        $mode = $request->bodyInt('mode', 1);
        $requireCookieConsent = $request->hasBody('require_cookie_consent');
        $enabled = $request->hasBody('switch');

        if ($name === '' || $link1 === '') {
            return $this->error(__('The required fields are not filled'));
        }

        $this->saveCounter->execute($id ?: null, $name, $link1, $link2, $mode, $requireCookieConsent, $enabled);

        redirect(self::URL);
    }

    public function toggle(Request $request, int $id): string
    {
        if ($this->isCsrfValid($request)) {
            $enabled = $request->bodyInt('enabled') === 1;
            $this->manageCounter->toggle($id, $enabled);
        }

        redirect(self::URL . '/' . $id);
    }

    public function up(Request $request, int $id): string
    {
        if ($this->isCsrfValid($request)) {
            $this->manageCounter->moveUp($id);
        }

        redirect(self::URL);
    }

    public function down(Request $request, int $id): string
    {
        if ($this->isCsrfValid($request)) {
            $this->manageCounter->moveDown($id);
        }

        redirect(self::URL);
    }

    public function deleteConfirm(int $id): string
    {
        $counter = $this->manageCounter->find($id);
        if ($counter === null) {
            return $this->error(__('Wrong data'));
        }

        $title = __('Delete:') . ' ' . $counter->name;
        $this->navChain->add(__('Counters'), self::URL);
        $this->navChain->add($title);
        $this->render->addData($this->menu($title));

        return $this->render->render('admin::counters_confirm', [
            'message'     => __('Do you really want to delete?'),
            'form_action' => self::URL . '/' . $id . '/delete',
            'back_url'    => self::URL,
        ]);
    }

    public function delete(Request $request, int $id): string
    {
        if ($this->isCsrfValid($request)) {
            $this->manageCounter->delete($id);
        }

        redirect(self::URL);
    }

    private function renderForm(?Counter $counter): string
    {
        $title = __('Counters');
        $this->navChain->add($title, self::URL);
        $this->render->addData($this->menu($title));

        return $this->render->render('admin::counters_form', [
            'form_action'            => self::URL . '/preview',
            'id'                     => $counter?->id,
            'name'                   => $counter?->name ?? '',
            'counter_1'              => $counter?->link1 ?? '',
            'counter_2'              => $counter?->link2 ?? '',
            'mode'                   => $counter?->mode ?? 0,
            'require_cookie_consent' => (bool) ($counter?->require_cookie_consent ?? false),
            'enabled'                => $counter === null ? true : $counter->switch === 1,
            'field_height'           => $this->currentUser->config->fieldHeight,
        ]);
    }

    private function modeName(int $mode): string
    {
        return match ($mode) {
            2       => __('On all pages showing option 1'),
            3       => __('On all pages showing option 2'),
            default => __('On the main showing option 1, on the other pages option 2'),
        };
    }

    private function error(string $message): string
    {
        $title = __('Counters');
        $this->render->addData($this->menu($title));

        return $this->render->render('system::pages/result', [
            'title'    => $title,
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

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }
}
