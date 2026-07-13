<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Controllers;

use HTMLPurifier;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Consent\Application\Services\ConsentService;
use Johncms\Modules\Consent\Application\Services\ConsentTitleFormatter;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class ConsentViewController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private ConsentService $consentService,
        private ConsentTitleFormatter $titleFormatter,
        private HTMLPurifier $purifier,
    ) {
        $this->controllerContext->initModule('consent');
    }

    public function __invoke(int $id): string
    {
        $consent = $this->consentService->getConsent($id);

        if ($consent === null || ! $consent->is_active || ! $consent->hasTextPage()) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'    => __('Consent'),
                'type'     => 'alert-danger',
                'message'  => __('The requested page was not found'),
                'back_url' => '/',
            ]);
        }

        $title = $this->titleFormatter->toPlainText($consent->title);
        $this->navChain->add($title);

        return $this->render->render('consent::view', [
            'title'      => $title,
            'page_title' => $title,
            'text'       => $this->purifier->purify($consent->text),
        ]);
    }
}
