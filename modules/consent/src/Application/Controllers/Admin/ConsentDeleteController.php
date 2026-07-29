<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Controllers\Admin;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Consent\Application\UseCases\DeleteConsentUseCase;
use Johncms\Modules\Consent\Domain\Repository\ConsentRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class ConsentDeleteController
{
    private const URL = '/admin/consents';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Session $session,
        private NavChain $navChain,
        private ConsentRepositoryInterface $repository,
        private DeleteConsentUseCase $deleteConsent,
    ) {
        $this->controllerContext->initModule('consent');
    }

    public function __invoke(Request $request, int $id): string
    {
        $consent = $this->repository->findById($id);
        if ($consent === null) {
            redirect(self::URL);
        }

        if ($request->getMethod() === 'POST') {
            $validator = new Validator(
                ['csrf_token' => $request->body('csrf_token', '')],
                ['csrf_token' => ['Csrf']]
            );

            if ($validator->isValid()) {
                $this->deleteConsent->execute($consent);
                $this->session->flash('success_message', __('Record deleted'));
                redirect(self::URL);
            }
        }

        $title = __('Delete consent') . ': ' . $consent->title;
        $this->navChain->add(__('Consents'), self::URL);
        $this->navChain->add($title);
        $this->render->addData([
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['consents' => true],
        ]);

        return $this->render->render('consent::admin/delete', [
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
