<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Controllers;

use HTMLPurifier;
use Johncms\Modules\Consent\Application\Services\ConsentService;
use Johncms\Modules\Consent\Application\Services\ConsentTitleFormatter;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Twig\Markup;

final readonly class ConsentViewController
{
    public function __construct(
        private NavChain $navChain,
        private ConsentService $consentService,
        private ConsentTitleFormatter $titleFormatter,
        private HTMLPurifier $purifier,
    ) {
    }

    public function __invoke(int $id): ViewResponse
    {
        $consent = $this->consentService->getConsent($id);

        if ($consent === null || ! $consent->is_active || ! $consent->hasTextPage()) {
            pageNotFound();
        }

        $title = $this->titleFormatter->toPlainText($consent->title);
        $this->navChain->add($title);

        return new ViewResponse('@consent/public/view.twig', [
            'title'      => $title,
            'page_title' => $title,
            'text'       => new Markup($this->purifier->purify($consent->text), 'UTF-8'),
        ]);
    }
}
