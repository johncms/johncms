<?php

declare(strict_types=1);

namespace Johncms\Modules\Redirect\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Redirect\Application\UseCases\RedirectByIdUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class RedirectController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private RedirectByIdUseCase $redirectByIdUseCase,
    ) {
        $this->controllerContext->initModule('redirect');
    }

    public function __invoke(Request $request): Response
    {
        $id = $request->queryInt('id', 0);
        $url = $request->queryParam('url', '');

        $url = $url !== '' ? strip_tags(rawurldecode(trim($url))) : '';

        if ($id) {
            return $this->handleById($id);
        }

        if ($url !== '') {
            return $this->handleByUrl($request, $url);
        }

        // Если нет ни id ни url, можно вернуть 404 или редирект на главную
        return new RedirectResponse('/');
    }

    private function handleById(int $id): Response
    {
        $redirectUrl = $this->redirectByIdUseCase->execute($id);
        if ($redirectUrl === null) {
            return new RedirectResponse('https://johncms.com/404');
        }

        return new RedirectResponse($redirectUrl);
    }

    private function handleByUrl(Request $request, string $url): Response
    {
        $submit = $request->hasBody('submit');
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';

        if ($submit) {
            return new RedirectResponse($url);
        }

        $title = __('Redirect to an external link');
        $this->navChain->add($title);

        return new Response($this->render->render(
            'redirect::index',
            [
                'title'        => $title,
                'page_title'   => $title,
                'redirect_url' => rawurlencode($url),
                'referer'      => htmlspecialchars($referer),
                'url'          => $url,
            ]
        ));
    }
}
