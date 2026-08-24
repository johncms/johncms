<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Ip;

use Johncms\Auth\CurrentUser;
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
use Johncms\Http\View\ViewResponse;

final readonly class IpBanController
{
    private const URL = '/admin/ip-bans';

    public function __construct(
        private Environment $environment,
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private GetIpBanListUseCase $getList,
        private PrepareIpBanUseCase $prepareIpBan,
        private StoreIpBanUseCase $storeIpBan,
        private ManageIpBanUseCase $manageIpBan,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function index(): ViewResponse
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

        return new ViewResponse('@admin/ip-bans.twig', $this->menuData($meta->title, $title) + [
            'items'        => array_map(fn (BanIp $ban): array => $this->listRow($ban), $bans->all()),
            'total'        => $pagination->getTotal(),
            'per_page'     => $pagination->getPerPage(),
            'show_actions' => true,
            'message'      => '',
            'add_url'      => self::URL . '/new',
            'search_url'   => self::URL . '/search',
            'clear_url'    => self::URL . '/clear',
            'pagination'   => $pagination->render(),
        ]);
    }

    /**
     * @param list<string> $errors
     */
    public function newForm(array $errors = []): ViewResponse
    {
        $title = __('Add Ban');
        $this->navChain->add(__('Ban by IP'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/ip-ban-form.twig', $this->menuData($title, $title) + [
            'form_action'  => self::URL . '/new',
            'errors'       => $errors,
            'field_height' => $this->currentUser->user()->config->fieldHeight,
        ]);
    }

    public function prepare(Request $request): ViewResponse
    {
        $term = $request->bodyInt('term', 1);
        $url = trim($request->body('url', ''));
        $reason = trim($request->body('reason', ''));

        $result = $this->prepareIpBan->execute($request->body('ip', ''), $this->environment->getClientInfo());

        if ($result->hasErrors()) {
            return $this->newForm($result->errors);
        }

        if ($result->hasConflicts()) {
            return $this->renderConflicts($result->conflicts);
        }

        return $this->renderConfirm($result->ip1, $result->ip2, $result->mode, $term, $url, $reason);
    }

    public function store(Request $request): ViewResponse
    {
        $ip1 = $request->bodyInt('ip1');
        $ip2 = $request->bodyInt('ip2');
        if ($ip1 <= 0 || $ip2 <= 0) {
            return $this->newForm([__('Invalid IP')]);
        }

        $this->storeIpBan->execute(
            $ip1,
            $ip2,
            $request->bodyInt('term', 1),
            trim($request->body('url', '')),
            $this->currentUser->user()->name,
            trim($request->body('reason', '')),
        );

        redirect(self::URL);
    }

    public function searchForm(): ViewResponse
    {
        $title = __('Search');
        $this->navChain->add(__('Ban by IP'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/ip-ban-search.twig', $this->menuData($title, $title) + [
            'form_action' => self::URL . '/search',
        ]);
    }

    public function search(Request $request): ViewResponse
    {
        $ip = ip2long(trim($request->body('ip', '')));
        if ($ip === false) {
            return $this->error(__('Invalid IP'));
        }

        $ban = $this->manageIpBan->findByIp($ip);
        if ($ban === null) {
            return $this->error(__('This address not in the database'), 'alert-info');
        }

        redirect(self::URL . '/' . $ban->id);
    }

    public function detail(int $id): ViewResponse
    {
        $ban = $this->manageIpBan->findById($id);
        if ($ban === null) {
            return $this->error(__('This address not in the database'), 'alert-info');
        }

        $title = __('Ban details');
        $this->navChain->add(__('Ban by IP'), self::URL);
        $this->navChain->add($title);

        $type = IpBanType::fromValueOrBlock($ban->ban_type);

        return new ViewResponse('@admin/ip-ban-detail.twig', $this->menuData($title, $title) + [
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

    public function delete(int $id): ViewResponse
    {
        $this->manageIpBan->delete($id);

        redirect(self::URL);
    }

    public function clearConfirm(): ViewResponse
    {
        $title = __('Ban by IP');
        $this->navChain->add($title, self::URL);

        return new ViewResponse('@admin/ip-ban-clear-confirm.twig', $this->menuData($title, $title) + [
            'message'      => __('Are you sure you wan to unban all IP?'),
            'form_action'  => self::URL . '/clear',
            'confirm_name' => __('Perform'),
            'back_url'     => self::URL,
        ]);
    }

    public function clear(): ViewResponse
    {
        $this->manageIpBan->clearAll();

        redirect(self::URL);
    }

    /**
     * @param \Illuminate\Support\Collection<int, BanIp> $conflicts
     */
    private function renderConflicts(\Illuminate\Support\Collection $conflicts): ViewResponse
    {
        $title = __('Add Ban');
        $this->navChain->add(__('Ban by IP'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/ip-bans.twig', $this->menuData($title, $title) + [
            'items'        => $conflicts->map(fn (BanIp $ban): array => $this->listRow($ban))->all(),
            'total'        => $conflicts->count(),
            'per_page'     => $this->currentUser->user()->config->kmess,
            'show_actions' => false,
            'message'      => __('Address you entered conflicts with other who in the database'),
            'add_url'      => self::URL . '/new',
            'search_url'   => self::URL . '/search',
            'clear_url'    => self::URL . '/clear',
            'pagination'   => null,
        ]);
    }

    private function renderConfirm(int $ip1, int $ip2, string $mode, int $term, string $url, string $reason): ViewResponse
    {
        $title = __('Add Ban');
        $this->navChain->add(__('Ban by IP'), self::URL);
        $this->navChain->add($title);

        [$modeName, $modeValue] = match ($mode) {
            'range' => [__('Ban range address'), long2ip($ip1) . ' - ' . long2ip($ip2)],
            'mask'  => [__('Ban on the subnet mask'), long2ip($ip1) . ' - ' . long2ip($ip2)],
            default => [__('Ban IP address'), long2ip($ip1)],
        };

        $type = IpBanType::fromValueOrBlock($term);

        return new ViewResponse('@admin/ip-ban-confirm.twig', $this->menuData($title, $title) + [
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

    private function error(string $message, string $type = 'alert-danger'): ViewResponse
    {
        $title = __('Ban by IP');

        return new ViewResponse('@admin/pages/result.twig', $this->menuData($title, $title) + [
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
}
