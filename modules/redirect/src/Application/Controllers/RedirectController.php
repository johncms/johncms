<?php

declare(strict_types=1);

namespace Johncms\Modules\Redirect\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Redirect\Application\UseCases\RedirectByIdUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class RedirectController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private RedirectByIdUseCase $redirectByIdUseCase,
    ) {
        $this->controllerContext->initModule('redirect');
    }

    public function __invoke(): string
    {
        $id = (int) $this->request->getQuery('id', 0);
        $url = $this->request->getQuery('url', '');

        $url = $url !== '' ? strip_tags(rawurldecode(trim($url))) : '';

        if ($id) {
            return $this->handleById($id);
        }

        if ($url !== '') {
            return $this->handleByUrl($url);
        }

        // Если нет ни id ни url, можно вернуть 404 или редирект на главную
        header('Location: /');
        exit;
    }

    private function handleById(int $id): string
    {
        $redirectUrl = $this->redirectByIdUseCase->execute($id);
        if ($redirectUrl === null) {
            header('Location: https://johncms.com/404');
            exit;
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    private function handleByUrl(string $url): string
    {
        $submit = $this->request->getPost('submit') !== null;
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';

        if ($submit) {
            header('Location: ' . $url);
            exit;
        }

        $title = __('Redirect to an external link');
        $this->navChain->add($title);

        return $this->render->render(
            'redirect::index',
            [
                'title'        => $title,
                'page_title'   => $title,
                'redirect_url' => rawurlencode($url),
                'referer'      => htmlspecialchars($referer),
                'url'          => $url,
            ]
        );
    }
}
