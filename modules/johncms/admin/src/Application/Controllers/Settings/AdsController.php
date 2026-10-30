<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Admin\Application\DTO\AdFormDTO;
use Johncms\Modules\Admin\Application\Services\AdRowMapper;
use Johncms\Modules\Admin\Application\UseCases\GetAdListUseCase;
use Johncms\Modules\Admin\Application\UseCases\ManageAdUseCase;
use Johncms\Modules\Admin\Application\UseCases\SaveAdUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\AdLimitReachedException;
use Johncms\Modules\Admin\Domain\Models\Ad;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Validator\Rules\NotEmpty;
use Johncms\Validator\Rules\Pattern;
use Johncms\Validator\Rules\WebAddress;
use Johncms\Validator\ValidatorInterface;

final readonly class AdsController
{
    private const URL = '/admin/ads';

    public function __construct(
        private NavChain $navChain,
        private GetAdListUseCase $getList,
        private SaveAdUseCase $saveAd,
        private ManageAdUseCase $manageAd,
        private AdRowMapper $rowMapper,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
        private ValidatorInterface $validator,
    ) {
    }

    public function index(Request $request): ViewResponse
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

        return new ViewResponse('@admin/ads.twig', $this->menu($meta->title, $title) + [
            'items'      => $this->rowMapper->mapMany($ads),
            'total'      => $pagination->getTotal(),
            'per_page'   => $pagination->getPerPage(),
            'offset'     => $pagination->getOffset(),
            'page'       => $pagination->getCurrentPage(),
            'type'       => $type,
            'filters'    => $this->filters($type, $this->getList->countsByType()),
            'add_url'    => self::URL . '/new',
            'clear_url'  => self::URL . '/clear',
            'pagination' => $pagination->render(),
            'success_message' => (string) $this->session->getFlash('success_message'),
            'error_message'   => (string) $this->session->getFlash('error_message'),
        ]);
    }

    public function newForm(): ViewResponse
    {
        return $this->renderForm(null, $this->defaultFields());
    }

    public function editForm(int $id): ViewResponse
    {
        $ad = $this->manageAd->find($id);
        if ($ad === null) {
            return $this->error(__('Wrong data'));
        }

        return $this->renderForm($id, $this->fieldsFromAd($ad));
    }

    public function store(Request $request): ViewResponse
    {
        $id = $request->bodyInt('id') ?: null;
        $fields = $this->fieldsFromRequest($request);
        $errors = $this->validate($fields);

        if ($errors !== []) {
            return $this->renderForm($id, $fields, $errors);
        }

        $isUpdate = $this->saveAd->execute($id, $this->dtoFromFields($fields));

        $this->session->flash('success_message', $isUpdate ? __('Link successfully changed') : __('Link successfully added'));
        redirect($this->listUrl((int) $fields['type'], 1));
    }

    public function up(Request $request, int $id): ViewResponse
    {
        return $this->reorder($request, $id, 'up');
    }

    public function down(Request $request, int $id): ViewResponse
    {
        return $this->reorder($request, $id, 'down');
    }

    public function toggle(Request $request, int $id): ViewResponse
    {
        $ad = $this->manageAd->find($id);
        if ($ad === null) {
            redirect(self::URL);
        }

        try {
            $shown = $this->manageAd->toggle($ad);
            $this->session->flash('success_message', $shown ? __('The link is shown') : __('The link is hidden'));
        } catch (AdLimitReachedException) {
            $this->session->flash('error_message', __('The link has used up its hits or days. Raise the limit in its settings to show it again.'));
        }

        redirect($this->listUrl($ad->type, $request->bodyInt('page', 1)));
    }

    public function deleteConfirm(int $id): ViewResponse
    {
        $ad = $this->manageAd->find($id);
        if ($ad === null) {
            return $this->error(__('Wrong data'));
        }

        $title = __('Delete');
        $this->navChain->add(__('Advertisement'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/ad-confirm.twig', $this->menu($title, $title) + [
            'message'     => __('Are you sure want to delete link?'),
            'form_action' => self::URL . '/' . $id . '/delete',
            'back_url'    => self::URL . '?type=' . $ad->type,
        ]);
    }

    public function delete(Request $request, int $id): ViewResponse
    {
        $ad = $this->manageAd->find($id);
        if ($ad === null) {
            redirect(self::URL);
        }

        $this->manageAd->delete($id);
        $this->session->flash('success_message', __('The link is deleted'));

        redirect($this->listUrl($ad->type, $request->bodyInt('page', 1)));
    }

    public function clearConfirm(Request $request): ViewResponse
    {
        $title = __('Delete inactive links');
        $data = [
            'message'     => __('Are you sure you want to delete all inactive links?'),
            'form_action' => self::URL . '/clear',
            'back_url'    => self::URL,
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@admin/ad-clear-dialog.twig', $data);
        }

        $this->navChain->add(__('Advertisement'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/ad-clear-confirm.twig', $this->menu($title, $title) + [
            'dialog' => $data,
        ]);
    }

    public function clear(): ViewResponse
    {
        $this->manageAd->deleteInactive();
        $this->session->flash('success_message', __('Inactive links deleted'));

        redirect(self::URL);
    }

    private function reorder(Request $request, int $id, string $direction): ViewResponse
    {
        $ad = $this->manageAd->find($id);
        if ($ad === null) {
            redirect(self::URL);
        }

        $direction === 'up' ? $this->manageAd->moveUp($id) : $this->manageAd->moveDown($id);

        redirect($this->listUrl($ad->type, $request->bodyInt('page', 1)));
    }

    /**
     * The list of a place at the page the action was taken on; the pagination guard moves a
     * page that no longer exists back to the last one.
     */
    private function listUrl(int $type, int $page): string
    {
        $query = array_filter(['type' => $type, 'page' => $page > 1 ? $page : 0]);

        return self::URL . ($query === [] ? '' : '?' . http_build_query($query));
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, list<string>>
     */
    private function validate(array $fields): array
    {
        return $this->validator->validate($fields, [
            'link'  => [new WebAddress()],
            'name'  => [new NotEmpty(message: __('Enter the title of the link'))],
            'color' => [
                new Pattern(
                    '/^[\da-f]{6}$/i',
                    allowEmpty: true,
                    message: __('Enter the color as six hexadecimal digits, for example FF0000')
                ),
            ],
        ])->getErrors();
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromRequest(Request $request): array
    {
        return [
            'link'       => trim($request->body('link', '')),
            'name'       => trim($request->body('name', '')),
            'color'      => ltrim(trim($request->body('color', '')), '#'),
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
            'link'       => '',
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
     * @param array<string, list<string>> $errors
     */
    private function renderForm(?int $id, array $fields, array $errors = []): ViewResponse
    {
        $title = $id ? __('Edit link') : __('Add link');
        $this->navChain->add(__('Advertisement'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/ad-form.twig', $this->menu($title, $title) + [
            'form_action'      => self::URL,
            'id'               => $id,
            'fields'           => $fields,
            'errors'           => $errors,
            'audience_options' => $this->audienceOptions(),
            'place_options'    => $this->placeOptions(),
            'layout_options'   => $this->layoutOptions(),
        ]);
    }

    /**
     * @return list<array{value: int, label: string, hint: string}>
     */
    private function audienceOptions(): array
    {
        return [
            ['value' => 0, 'label' => __('Everyone'), 'hint' => __('Visitors who are signed in and guests can see this link.')],
            ['value' => 1, 'label' => __('Guests'), 'hint' => __('Show this link only to visitors who are not signed in.')],
            ['value' => 2, 'label' => __('Users'), 'hint' => __('Show this link only to signed-in users.')],
        ];
    }

    /**
     * @return list<array{value: int, label: string, hint: string}>
     */
    private function placeOptions(): array
    {
        return [
            ['value' => 0, 'label' => __('Before the menu'), 'hint' => __('Above the site menu.')],
            ['value' => 1, 'label' => __('After the menu'), 'hint' => __('Below the site menu.')],
            ['value' => 2, 'label' => __('At the top of the page'), 'hint' => __('At the start of the page content.')],
            ['value' => 3, 'label' => __('At the bottom of the page'), 'hint' => __('After the page content.')],
        ];
    }

    /**
     * @return list<array{value: int, label: string, hint: string}>
     */
    private function layoutOptions(): array
    {
        return [
            ['value' => 0, 'label' => __('All pages'), 'hint' => __('Show on the homepage and every other page.')],
            ['value' => 1, 'label' => __('Only on Homepage'), 'hint' => __('Show on the homepage only.')],
            ['value' => 2, 'label' => __('On all, except Homepage'), 'hint' => __('Show on every page except the homepage.')],
        ];
    }

    /**
     * @param array<int, int> $counts
     * @return list<array{url: string, title: string, active: bool, count: int, tone: string}>
     */
    private function filters(int $type, array $counts): array
    {
        $filters = [];
        foreach ($this->placeOptions() as $option) {
            $filters[] = [
                'url'    => $option['value'] === 0 ? self::URL : self::URL . '?type=' . $option['value'],
                'title'  => $option['label'],
                'active' => $type === $option['value'],
                'count'  => $counts[$option['value']] ?? 0,
                'tone'   => '',
            ];
        }

        return $filters;
    }

    private function clampType(int $type): int
    {
        return $type >= 0 && $type <= 3 ? $type : 0;
    }

    private function error(string $message): ViewResponse
    {
        $title = __('Advertisement');

        return new ViewResponse('@admin/pages/result.twig', $this->menu($title, $title) + [
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
}
