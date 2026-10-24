<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Application\Services\UserBansMapper;
use Johncms\Modules\Admin\Application\Services\UserCardMapper;
use Johncms\Modules\Admin\Application\UseCases\GetUserBansUseCase;
use Johncms\NavChain;
use Johncms\Users\Bans\BanAccess;
use Johncms\Users\Bans\BanDurationUnit;
use Johncms\Users\Bans\BanException;
use Johncms\Users\Bans\BanKind;
use Johncms\Users\Bans\BanManager;
use Johncms\Users\Bans\BanNotFoundException;
use Johncms\Users\User;

/**
 * The bans of an account in the panel: the history, and giving, lifting and deleting them by the
 * rules the profile of the site follows too (BanManager, BanAccess). Giving one and every removal
 * open as dialogs; a plain visit gets each as a page.
 */
final readonly class UserBansController
{
    public function __construct(
        private NavChain $navChain,
        private GetUserBansUseCase $getBans,
        private BanManager $bans,
        private BanAccess $access,
        private AccessCheckerInterface $accessChecker,
        private UserBansMapper $mapper,
        private UserCardMapper $card,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
    ) {
    }

    public function index(int $id): ViewResponse
    {
        try {
            $user = $this->getBans->user($id);
        } catch (UserNotFoundException) {
            return $this->refused(__('User does not exists'), false, 404);
        }

        $pagination = $this->paginationFactory->create($this->getBans->count($user));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $base = '/admin/users/' . $user->id . '/bans';

        return $this->page($user, '@admin/user/bans.twig', [
            'bans'       => $this->mapper->rows($user, $this->getBans->page($user, $pagination->getPerPage(), $pagination->getOffset())),
            'total'      => $pagination->getTotal(),
            'pagination' => $pagination->render(),
            'ban_url'    => $this->mapper->mayBan($user) ? $base . '/new' : '',
            'clear_url'  => $this->mapper->mayClear($user) ? $base . '/clear' : '',
        ]);
    }

    public function createForm(Request $request, int $id): ViewResponse
    {
        try {
            $user = $this->target($id);
        } catch (UserNotFoundException) {
            return $this->refused(__('User does not exists'), $request->isXmlHttpRequest(), 404);
        } catch (BanException $e) {
            return $this->refused($e->getMessage(), $request->isXmlHttpRequest());
        }

        return $this->form($user, $request->isXmlHttpRequest(), [], '');
    }

    public function create(Request $request, int $id): ViewResponse
    {
        try {
            $user = $this->target($id);
        } catch (UserNotFoundException) {
            return $this->refused(__('User does not exists'), false, 404);
        } catch (BanException $e) {
            return $this->refused($e->getMessage(), false);
        }

        $values = [
            'kind'   => $request->bodyInt('kind'),
            'amount' => $request->bodyInt('amount'),
            'unit'   => $request->bodyInt('unit'),
            'reason' => $request->body('reason'),
        ];

        try {
            $this->bans->apply($user, $values['kind'], $values['unit'], $values['amount'], $values['reason']);
        } catch (BanException $e) {
            return $this->form($user, false, $values, $e->getMessage());
        }

        $this->session->flash('success_message', __('User banned'));
        redirect('/admin/users/' . $user->id . '/bans');
    }

    public function liftConfirm(Request $request, int $id, int $banId): ViewResponse
    {
        return $this->confirmAbout($request, $id, $banId, true, static fn (User $user, int $ban): array => [
            'title'   => __('Lift the ban'),
            'message' => __('The ban ends now. Its record stays in the history.'),
            'action'  => '/admin/users/' . $user->id . '/bans/' . $ban . '/lift',
            'confirm' => __('Lift the ban'),
        ]);
    }

    public function lift(int $id, int $banId): ViewResponse
    {
        return $this->act($id, $banId, true, function (User $user, int $banId): void {
            $this->bans->lift($this->bans->findActive($user->id, $banId));
            $this->session->flash('success_message', __('Ban terminated'));
        });
    }

    public function deleteConfirm(Request $request, int $id, int $banId): ViewResponse
    {
        return $this->confirmAbout($request, $id, $banId, false, static fn (User $user, int $ban): array => [
            'title'   => __('Delete the record of the ban'),
            'message' => __('The ban goes with its record in the history, and the karma it cost comes back.'),
            'action'  => '/admin/users/' . $user->id . '/bans/' . $ban . '/delete',
            'confirm' => __('Delete'),
        ]);
    }

    public function delete(int $id, int $banId): ViewResponse
    {
        return $this->act($id, $banId, false, function (User $user, int $banId): void {
            $this->bans->delete($this->bans->find($user->id, $banId));
            $this->session->flash('success_message', __('Ban deleted'));
        });
    }

    public function clearConfirm(Request $request, int $id): ViewResponse
    {
        return $this->confirmAbout($request, $id, null, false, static fn (User $user): array => [
            'title'   => __('Clear the history'),
            'message' => __('Every ban that has run out or was lifted goes, with the karma it cost. The bans in force stay.'),
            'action'  => '/admin/users/' . $user->id . '/bans/clear',
            'confirm' => __('Clear'),
        ]);
    }

    public function clear(int $id): ViewResponse
    {
        return $this->act($id, null, false, function (User $user): void {
            $this->bans->clearHistory($user->id);
            $this->session->flash('success_message', __('Violations history cleared'));
        });
    }

    /**
     * The account the visitor is about to ban, once it is known they may.
     *
     * @throws UserNotFoundException
     * @throws BanException
     */
    private function target(int $id): User
    {
        $user = $this->getBans->user($id);

        if (! $this->mapper->mayBan($user)) {
            throw new BanException(d__('system', 'You do not have enought rights to ban this user'));
        }

        return $user;
    }

    /**
     * The form of a ban: offered as the kinds and the lengths the visitor may give.
     *
     * @param array<string, mixed> $values What was sent last time; empty for a new form.
     */
    private function form(User $user, bool $dialog, array $values, string $error): ViewResponse
    {
        $kinds = $this->access->applicableKinds();
        $units = array_values(array_filter(
            BanDurationUnit::cases(),
            fn (BanDurationUnit $unit): bool => match ($unit) {
                BanDurationUnit::DAYS         => $this->accessChecker->allows(CorePermissions::USERS_BAN_MANAGE),
                BanDurationUnit::UNTIL_LIFTED => $this->accessChecker->allows(CorePermissions::USERS_BAN_PERMANENT),
                default                       => true,
            }
        ));

        $data = [
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'action'    => '/admin/users/' . $user->id . '/bans/new',
            'back_url'  => '/admin/users/' . $user->id . '/bans',
            'kinds'     => array_map(static fn (BanKind $kind): array => ['value' => $kind->value, 'title' => $kind->title()], $kinds),
            'units'     => array_map(static fn (BanDurationUnit $unit): array => ['value' => $unit->value, 'title' => $unit->title()], $units),
            'values'    => $values + ['kind' => $kinds[0]->value ?? 0, 'amount' => 12, 'unit' => BanDurationUnit::HOURS->value, 'reason' => ''],
            'error'     => $error,
        ];

        if ($dialog) {
            return new ViewResponse('@admin/user/ban-dialog.twig', $data);
        }

        $this->navChain->add(__('Users'), '/admin/users');
        $this->navChain->add($user->name, '/admin/users/' . $user->id);
        $this->navChain->add(__('Ban'));

        return new ViewResponse('@admin/user/ban-form.twig', ['title' => __('Ban'), 'dialog' => $data]);
    }

    /**
     * A confirmation about one ban of the account, or about all of them when there is no ban.
     *
     * @param callable(User, int): array<string, string> $describe
     */
    private function confirmAbout(Request $request, int $id, ?int $banId, bool $lifting, callable $describe): ViewResponse
    {
        $dialog = $request->isXmlHttpRequest();

        try {
            $user = $this->guarded($id, $lifting);
            if ($banId !== null && $lifting) {
                $this->bans->findActive($user->id, $banId);
            } elseif ($banId !== null) {
                $this->bans->find($user->id, $banId);
            }
        } catch (UserNotFoundException) {
            return $this->refused(__('User does not exists'), $dialog, 404);
        } catch (BanNotFoundException) {
            return $this->refused(__('Wrong data'), $dialog, 404);
        } catch (BanException $e) {
            return $this->refused($e->getMessage(), $dialog);
        }

        $back = '/admin/users/' . $user->id . '/bans';
        $data = $describe($user, (int) $banId) + [
            'user'     => ['id' => $user->id, 'name' => $user->name],
            'fields'   => [],
            'back_url' => $back,
        ];

        if ($dialog) {
            return new ViewResponse('@admin/pages/confirm-dialog.twig', $data);
        }

        $this->navChain->add($user->name, '/admin/users/' . $user->id);
        $this->navChain->add($data['title']);

        return new ViewResponse('@admin/pages/confirm.twig', ['title' => $data['title'], 'dialog' => $data]);
    }

    /**
     * @param callable(User, int): void $action
     */
    private function act(int $id, ?int $banId, bool $lifting, callable $action): ViewResponse
    {
        try {
            $user = $this->guarded($id, $lifting);
            $action($user, (int) $banId);
        } catch (UserNotFoundException) {
            return $this->refused(__('User does not exists'), false, 404);
        } catch (BanNotFoundException) {
            return $this->refused(__('Wrong data'), false, 404);
        } catch (BanException $e) {
            return $this->refused($e->getMessage(), false);
        }

        redirect('/admin/users/' . $user->id . '/bans');
    }

    /**
     * The account whose ban is about to be lifted or deleted, once it is known the visitor may.
     *
     * @throws UserNotFoundException
     * @throws BanException
     */
    private function guarded(int $id, bool $lifting): User
    {
        $user = $this->getBans->user($id);
        $allowed = $lifting ? $this->access->mayLift($user->id) : $this->access->mayDestroy($user->id);

        return $allowed ? $user : throw new BanException(__('You cannot change this account'));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function page(User $user, string $template, array $data): ViewResponse
    {
        $this->navChain->add(__('Users'), '/admin/users');
        $this->navChain->add($user->name, '/admin/users/' . $user->id);
        $this->navChain->add(__('Bans'));

        return new ViewResponse($template, $data + [
            'title'           => $user->name . ' — ' . __('Bans'),
            'page_title'      => $user->name,
            'usr_menu'        => ['userlist' => true],
            'user'            => $this->card->head($user),
            'tabs'            => $this->card->tabs($user, 'bans'),
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }

    private function refused(string $message, bool $dialog, int $status = 403): ViewResponse
    {
        $data = [
            'title'    => __('Bans'),
            'type'     => 'alert-danger',
            'message'  => $message,
            'back_url' => '/admin/users',
        ];

        if ($dialog) {
            return new ViewResponse('@admin/pages/result-dialog.twig', $data);
        }

        return new ViewResponse('@admin/pages/result.twig', $data + ['page_title' => __('Bans')], $status);
    }
}
