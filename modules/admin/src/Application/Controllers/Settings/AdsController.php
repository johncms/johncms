<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Admin\Application\DTO\AdFormDTO;
use Johncms\Modules\Admin\Application\Services\AdRowMapper;
use Johncms\Modules\Admin\Application\UseCases\GetAdListUseCase;
use Johncms\Modules\Admin\Application\UseCases\ManageAdUseCase;
use Johncms\Modules\Admin\Application\UseCases\SaveAdUseCase;
use Johncms\Modules\Admin\Domain\Models\Ad;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class AdsController
{
    private const URL = '/admin/ads';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetAdListUseCase $getList,
        private SaveAdUseCase $saveAd,
        private ManageAdUseCase $manageAd,
        private AdRowMapper $rowMapper,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(Request $request): string
    {
        $type = $this->clampType($request->queryInt('type'));

        $pagination = $this->paginationFactory->create($this->getList->count($type));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $ads = $this->getList->getPage($type, $pagination->getPerPage(), $pagination->getOffset());

        $title = __('Advertisement');
        $this->navChain->add($title, self::URL);

        $meta = new PageMeta($title, $pagination->getCurrentPage());
        $this->render->addData($this->menu($meta->title, $title));

        return $this->render->render('admin::ads_index', [
            'items'      => $this->rowMapper->mapMany($ads),
            'total'      => $pagination->getTotal(),
            'per_page'   => $pagination->getPerPage(),
            'type'       => $type,
            'filters'    => $this->filters($type),
            'add_url'    => self::URL . '/new',
            'clear_url'  => self::URL . '/clear',
            'pagination' => $pagination->render(),
        ]);
    }

    public function newForm(): string
    {
        return $this->renderForm(null, $this->defaultFields());
    }

    public function editForm(int $id): string
    {
        $ad = $this->manageAd->find($id);
        if ($ad === null) {
            return $this->error(__('Wrong data'));
        }

        return $this->renderForm($id, $this->fieldsFromAd($ad));
    }

    public function store(Request $request): string
    {
        if (! $this->isCsrfValid($request)) {
            return $this->error(__('Wrong data'));
        }

        $id = $request->bodyInt('id') ?: null;
        $fields = $this->fieldsFromRequest($request);
        $errors = $this->validate($fields);

        if ($errors !== []) {
            return $this->renderForm($id, $fields, $errors);
        }

        $isUpdate = $this->saveAd->execute($id, $this->dtoFromFields($fields));

        $this->session->flash('success_message', $isUpdate ? __('Link successfully changed') : __('Link successfully added'));
        redirect(self::URL . '?type=' . $fields['type']);
    }

    public function up(Request $request, int $id): string
    {
        return $this->reorder($request, $id, 'up');
    }

    public function down(Request $request, int $id): string
    {
        return $this->reorder($request, $id, 'down');
    }

    public function toggle(Request $request, int $id): string
    {
        $type = $this->manageAd->find($id)?->type ?? 0;
        if ($this->isCsrfValid($request)) {
            $this->manageAd->toggle($id);
        }

        redirect(self::URL . '?type=' . $type);
    }

    public function deleteConfirm(int $id): string
    {
        $ad = $this->manageAd->find($id);
        if ($ad === null) {
            return $this->error(__('Wrong data'));
        }

        $title = __('Delete');
        $this->navChain->add(__('Advertisement'), self::URL);
        $this->navChain->add($title);
        $this->render->addData($this->menu($title, $title));

        return $this->render->render('admin::ads_confirm', [
            'message'     => __('Are you sure want to delete link?'),
            'form_action' => self::URL . '/' . $id . '/delete',
            'back_url'    => self::URL . '?type=' . $ad->type,
        ]);
    }

    public function delete(Request $request, int $id): string
    {
        $type = $this->manageAd->find($id)?->type ?? 0;
        if ($this->isCsrfValid($request)) {
            $this->manageAd->delete($id);
        }

        redirect(self::URL . '?type=' . $type);
    }

    public function clearConfirm(): string
    {
        $title = __('Advertisement');
        $this->navChain->add($title, self::URL);
        $this->render->addData($this->menu($title, $title));

        return $this->render->render('admin::ads_confirm', [
            'message'     => __('Are you sure you want to delete all inactive links?'),
            'form_action' => self::URL . '/clear',
            'back_url'    => self::URL,
        ]);
    }

    public function clear(Request $request): string
    {
        if ($this->isCsrfValid($request)) {
            $this->manageAd->deleteInactive();
        }

        redirect(self::URL);
    }

    private function reorder(Request $request, int $id, string $direction): string
    {
        $type = $this->manageAd->find($id)?->type ?? 0;
        if ($this->isCsrfValid($request)) {
            $direction === 'up' ? $this->manageAd->moveUp($id) : $this->manageAd->moveDown($id);
        }

        redirect(self::URL . '?type=' . $type);
    }

    /**
     * @param array<string, mixed> $fields
     * @return list<string>
     */
    private function validate(array $fields): array
    {
        $errors = [];
        if ($fields['link'] === '' || $fields['name'] === '') {
            $errors[] = __('The required fields are not filled');
        }

        $color = (string) $fields['color'];
        if ($color !== '') {
            if (preg_match('/[^\da-fA-F_]+/', $color)) {
                $errors[] = __('Invalid characters');
            }
            if (strlen($color) < 6) {
                $errors[] = __('Color is specified incorrectly');
            }
        }

        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromRequest(Request $request): array
    {
        return [
            'link'       => trim($request->body('link', '')),
            'name'       => trim($request->body('name', '')),
            'color'      => mb_substr(trim($request->body('color', '')), 0, 6),
            'count_link' => abs($request->bodyInt('count')),
            'day'        => abs($request->bodyInt('day')),
            'view'       => abs($request->bodyInt('view')),
            'type'       => $this->clampType($request->bodyInt('type')),
            'layout'     => abs($request->bodyInt('layout')),
            'show'       => $request->hasBody('show') ? 1 : 0,
            'bold'       => $request->hasBody('bold') ? 1 : 0,
            'italic'     => $request->hasBody('italic') ? 1 : 0,
            'underline'  => $request->hasBody('underline') ? 1 : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromAd(Ad $ad): array
    {
        return [
            'link'       => $ad->link,
            'name'       => $ad->name,
            'color'      => $ad->color,
            'count_link' => $ad->count_link,
            'day'        => $ad->day,
            'view'       => $ad->view,
            'type'       => $ad->type,
            'layout'     => $ad->layout,
            'show'       => $ad->show,
            'bold'       => $ad->bold,
            'italic'     => $ad->italic,
            'underline'  => $ad->underline,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFields(): array
    {
        return [
            'link'       => 'http://',
            'name'       => '',
            'color'      => '',
            'count_link' => 0,
            'day'        => 0,
            'view'       => 0,
            'type'       => 0,
            'layout'     => 0,
            'show'       => 0,
            'bold'       => 0,
            'italic'     => 0,
            'underline'  => 0,
        ];
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function dtoFromFields(array $fields): AdFormDTO
    {
        return new AdFormDTO(
            link: (string) $fields['link'],
            name: (string) $fields['name'],
            color: (string) $fields['color'],
            countLink: (int) $fields['count_link'],
            day: (int) $fields['day'],
            view: (int) $fields['view'],
            type: (int) $fields['type'],
            layout: (int) $fields['layout'],
            directLink: (bool) $fields['show'],
            bold: (bool) $fields['bold'],
            italic: (bool) $fields['italic'],
            underline: (bool) $fields['underline'],
        );
    }

    /**
     * @param array<string, mixed> $fields
     * @param list<string> $errors
     */
    private function renderForm(?int $id, array $fields, array $errors = []): string
    {
        $title = $id ? __('Edit link') : __('Add link');
        $this->navChain->add(__('Advertisement'), self::URL);
        $this->navChain->add($title);
        $this->render->addData($this->menu($title, $title));

        return $this->render->render('admin::ads_add', [
            'form_action' => self::URL,
            'id'          => $id,
            'fields'      => $fields,
            'errors'      => $errors,
        ]);
    }

    /**
     * @return list<array{url: string, name: string, active: bool}>
     */
    private function filters(int $type): array
    {
        return [
            ['url' => self::URL, 'name' => __('Before the menu'), 'active' => $type === 0],
            ['url' => self::URL . '?type=1', 'name' => __('After the menu'), 'active' => $type === 1],
            ['url' => self::URL . '?type=2', 'name' => __('At the top of the page'), 'active' => $type === 2],
            ['url' => self::URL . '?type=3', 'name' => __('At the bottom of the page'), 'active' => $type === 3],
        ];
    }

    private function clampType(int $type): int
    {
        return $type >= 0 && $type <= 3 ? $type : 0;
    }

    private function error(string $message): string
    {
        $title = __('Advertisement');
        $this->render->addData($this->menu($title, $title));

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
    private function menu(string $title, string $pageTitle): array
    {
        return [
            'title'       => $title,
            'page_title'  => $pageTitle,
            'module_menu' => ['ads' => true],
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
