<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Ip;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Admin\Application\UseCases\GetIpBanListUseCase;
use Johncms\Modules\Admin\Application\UseCases\ManageIpBanUseCase;
use Johncms\Modules\Admin\Application\UseCases\PrepareIpBanUseCase;
use Johncms\Modules\Admin\Application\UseCases\StoreIpBanUseCase;
use Johncms\Modules\Admin\Domain\Enums\IpBanType;
use Johncms\Modules\Admin\Domain\Models\BanIp;
use Johncms\NavChain;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class IpBanController
{
    private const URL = '/admin/ip-bans';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Environment $environment,
        private NavChain $navChain,
        private User $currentUser,
        private GetIpBanListUseCase $getList,
        private PrepareIpBanUseCase $prepareIpBan,
        private StoreIpBanUseCase $storeIpBan,
        private ManageIpBanUseCase $manageIpBan,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(): string
    {
        $pagination = $this->paginationFactory->create($this->getList->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $bans = $this->getList->getPage($pagination->getPerPage(), $pagination->getOffset());

        $title = __('Ban by IP');
        $this->navChain->add($title, self::URL);

        $meta = new PageMeta($title, $pagination->getCurrentPage());
        $this->render->addData($this->menuData($meta->title, $title));

        return $this->render->render('admin::ipban', [
            'items'      => array_map(fn (BanIp $ban): array => $this->listRow($ban), $bans->all()),
            'total'      => $pagination->getTotal(),
            'per_page'   => $pagination->getPerPage(),
            'no_buttons' => false,
            'message'    => null,
            'add_url'    => self::URL . '/new',
            'search_url' => self::URL . '/search',
            'clear_url'  => self::URL . '/clear',
            'pagination' => $pagination->render(),
        ]);
    }

    public function newForm(?string $error = null): string
    {
        $title = __('Add Ban');
        $this->navChain->add(__('Ban by IP'), self::URL);
        $this->navChain->add($title);
        $this->render->addData($this->menuData($title, $title));

        return $this->render->render('admin::ipban_add', [
            'form_action'   => self::URL . '/new',
            'error_message' => $error,
            'field_height'  => $this->currentUser->config->fieldHeight,
        ]);
    }

    public function prepare(): string
    {
        if (! $this->isCsrfValid()) {
            return $this->newForm(__('Wrong data'));
        }

        $term = $this->request->bodyInt('term', 1);
        $url = trim($this->request->body('url', ''));
        $reason = trim($this->request->body('reason', ''));

        $result = $this->prepareIpBan->execute($this->request->body('ip', ''), $this->environment->getClientInfo());

        if ($result->hasErrors()) {
            return $this->newForm(implode('<br>', $result->errors));
        }

        if ($result->hasConflicts()) {
            return $this->renderConflicts($result->conflicts);
        }

        return $this->renderConfirm($result->ip1, $result->ip2, $result->mode, $term, $url, $reason);
    }

    public function store(): string
    {
        if (! $this->isCsrfValid()) {
            return $this->newForm(__('Wrong data'));
        }

        $ip1 = $this->request->bodyInt('ip1');
        $ip2 = $this->request->bodyInt('ip2');
        if ($ip1 <= 0 || $ip2 <= 0) {
            return $this->newForm(__('Invalid IP'));
        }

        $this->storeIpBan->execute(
            $ip1,
            $ip2,
            $this->request->bodyInt('term', 1),
            trim($this->request->body('url', '')),
            $this->currentUser->name,
            trim($this->request->body('reason', '')),
        );

        redirect(self::URL);
    }

    public function searchForm(): string
    {
        $title = __('Search');
        $this->navChain->add(__('Ban by IP'), self::URL);
        $this->navChain->add($title);
        $this->render->addData($this->menuData($title, $title));

        return $this->render->render('admin::ipban_search', [
            'form_action' => self::URL . '/search',
        ]);
    }

    public function search(): string
    {
        if (! $this->isCsrfValid()) {
            return $this->error(__('Wrong data'));
        }

        $ip = ip2long(trim($this->request->body('ip', '')));
        if ($ip === false) {
            return $this->error(__('Invalid IP'));
        }

        $ban = $this->manageIpBan->findByIp($ip);
        if ($ban === null) {
            return $this->error(__('This address not in the database'), 'alert-info');
        }

        redirect(self::URL . '/' . $ban->id);
    }

    public function detail(int $id): string
    {
        $ban = $this->manageIpBan->findById($id);
        if ($ban === null) {
            return $this->error(__('This address not in the database'), 'alert-info');
        }

        $title = __('Ban details');
        $this->navChain->add(__('Ban by IP'), self::URL);
        $this->navChain->add($title);
        $this->render->addData($this->menuData($title, $title));

        $type = IpBanType::fromValueOrBlock($ban->ban_type);

        return $this->render->render('admin::ipban_detail', [
            'ips'          => $this->formatIps($ban),
            'ban_type'     => $type->label(),
            'link'         => $type === IpBanType::REDIRECT ? $ban->link : '',
            'reason'       => $ban->reason !== '' ? $ban->reason : __('Not specified'),
            'who'          => $ban->who,
            'display_date' => date('d.m.Y', $ban->date),
            'display_time' => date('H:i:s', $ban->date),
            'delete_url'   => self::URL . '/' . $ban->id . '/delete',
            'back_url'     => self::URL,
        ]);
    }

    public function delete(int $id): string
    {
        if ($this->isCsrfValid()) {
            $this->manageIpBan->delete($id);
        }

        redirect(self::URL);
    }

    public function clearConfirm(): string
    {
        $title = __('Ban by IP');
        $this->navChain->add($title, self::URL);
        $this->render->addData($this->menuData($title, $title));

        return $this->render->render('admin::ipban_confirm', [
            'message'      => __('Are you sure you wan to unban all IP?'),
            'form_action'  => self::URL . '/clear',
            'confirm_name' => __('Perform'),
            'back_url'     => self::URL,
        ]);
    }

    public function clear(): string
    {
        if ($this->isCsrfValid()) {
            $this->manageIpBan->clearAll();
        }

        redirect(self::URL);
    }

    /**
     * @param \Illuminate\Support\Collection<int, BanIp> $conflicts
     */
    private function renderConflicts(\Illuminate\Support\Collection $conflicts): string
    {
        $title = __('Add Ban');
        $this->navChain->add(__('Ban by IP'), self::URL);
        $this->navChain->add($title);
        $this->render->addData($this->menuData($title, $title));

        return $this->render->render('admin::ipban', [
            'items'      => $conflicts->map(fn (BanIp $ban): array => $this->listRow($ban))->all(),
            'total'      => $conflicts->count(),
            'per_page'   => $this->currentUser->config->kmess,
            'no_buttons' => true,
            'message'    => __('Address you entered conflicts with other who in the database'),
            'add_url'    => self::URL . '/new',
            'search_url' => self::URL . '/search',
            'clear_url'  => self::URL . '/clear',
            'pagination' => '',
        ]);
    }

    private function renderConfirm(int $ip1, int $ip2, string $mode, int $term, string $url, string $reason): string
    {
        $title = __('Add Ban');
        $this->navChain->add(__('Ban by IP'), self::URL);
        $this->navChain->add($title);
        $this->render->addData($this->menuData($title, $title));

        [$modeName, $modeValue] = match ($mode) {
            'range' => [__('Ban range address'), long2ip($ip1) . ' - ' . long2ip($ip2)],
            'mask'  => [__('Ban on the subnet mask'), long2ip($ip1) . ' - ' . long2ip($ip2)],
            default => [__('Ban IP address'), long2ip($ip1)],
        };

        $type = IpBanType::fromValueOrBlock($term);

        return $this->render->render('admin::ipban_add_confirm', [
            'form_action'    => self::URL,
            'mode_name'      => $modeName,
            'mode_value'     => $modeValue,
            'ban_type_label' => $type->label(),
            'ban_url'        => $type === IpBanType::REDIRECT ? ($url !== '' ? $url : __('Default')) : '',
            'reason_display' => $reason !== '' ? $reason : __('Not specified'),
            'ip1'            => $ip1,
            'ip2'            => $ip2,
            'term'           => $term,
            'url'            => $url,
            'reason'         => $reason,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function listRow(BanIp $ban): array
    {
        return [
            'detail_url'   => self::URL . '/' . $ban->id,
            'ips'          => $this->formatIps($ban),
            'reason_label' => IpBanType::fromValueOrBlock($ban->ban_type)->label(),
        ];
    }

    private function formatIps(BanIp $ban): string
    {
        return $ban->ip1 === $ban->ip2
            ? long2ip($ban->ip1)
            : long2ip($ban->ip1) . ' - ' . long2ip($ban->ip2);
    }

    private function error(string $message, string $type = 'alert-danger'): string
    {
        $title = __('Ban by IP');
        $this->render->addData($this->menuData($title, $title));

        return $this->render->render('system::pages/result', [
            'title'    => $title,
            'type'     => $type,
            'message'  => $message,
            'back_url' => self::URL,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function menuData(string $title, string $pageTitle): array
    {
        return [
            'title'      => $title,
            'page_title' => $pageTitle,
            'sec_menu'   => ['ipban' => true],
        ];
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => $this->request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }
}
