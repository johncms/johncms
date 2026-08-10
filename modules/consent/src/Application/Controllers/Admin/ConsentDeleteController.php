<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Controllers\Admin;

use Johncms\Modules\Consent\Application\UseCases\DeleteConsentUseCase;
use Johncms\Modules\Consent\Domain\Repository\ConsentRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Johncms\Validator\Validator;

final readonly class ConsentDeleteController
{
    private const URL = '/admin/consents';

    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private ConsentRepositoryInterface $repository,
        private DeleteConsentUseCase $deleteConsent,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $consent = $this->repository->findById($id);
        if ($consent === null) {
            redirect(self::URL);
        }

        if ($request->getMethod() === 'POST') {
            $this->deleteConsent->execute($consent);
            $this->session->flash('success_message', __('Record deleted'));
            redirect(self::URL);
        }

        $title = __('Delete consent') . ': ' . $consent->title;
        $this->navChain->add(__('Consents'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@consent/admin/delete-confirm.twig', [
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['consents' => true],
        ] + [
            'consent'     => [
                'title'   => $consent->title,
                'context' => $consent->context,
                'version' => $consent->version,
            ],
            'form_action' => self::URL . '/' . $consent->id . '/delete',
            'back_url'    => self::URL,
        ]);
    }
}
