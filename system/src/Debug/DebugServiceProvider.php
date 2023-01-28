<?php

declare(strict_types=1);

namespace Johncms\Debug;

use DebugBar\JavascriptRenderer;
use Illuminate\Contracts\Events\Dispatcher;
use Johncms\AbstractServiceProvider;
use Johncms\Application;
use Johncms\Http\Request;
use Johncms\Users\User;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class DebugServiceProvider extends AbstractServiceProvider
{
    private ?DebugBar $debugBar = null;
    private ?JavascriptRenderer $javascriptRenderer = null;

    public function __construct(
        protected ContainerInterface $container,
        private Request $request,
        private Dispatcher $dispatcher,
        private ?User $user
    ) {
        parent::__construct($container);
    }

    public function register(): void
    {
        // Registering application event listeners
        if ($this->debugMode() && ! str_starts_with($this->request->getUri()->getPath(), '/_debugbar/')) {
            $this->dispatcher->listen(Application::BEFORE_HANDLE_REQUEST_EVENT, [$this, 'beforeEventHandler']);
            $this->dispatcher->listen(Application::AFTER_HANDLE_REQUEST_EVENT, [$this, 'afterEventHandler']);
        }
    }

    public function beforeEventHandler()
    {
        // Initialize the debugbar if necessary
        $this->debugBar = $this->container->get(DebugBar::class);
        $this->debugBar->addBootingTime();
        $this->debugBar->startApplicationMeasure();
        header('phpdebugbar-id: ' . $this->debugBar->getCurrentRequestId());
        $this->javascriptRenderer = $this->debugBar->getJavascriptRenderer();
        $this->javascriptRenderer->setBindAjaxHandlerToXHR();
        $this->javascriptRenderer->addAssets(['/assets/default/debugbar/custom.css'], ['/assets/default/debugbar/queryWidget.js']);
    }

    public function afterEventHandler(ResponseInterface $response)
    {
        // Collect data for debugbar and render html
        $contentType = $response->getHeader('content-type')[0] ?? 'text/html';
        if ($this->request->isXmlHttpRequest() || $contentType === 'application/json') {
            $this->debugBar->stackData();
        } else {
            $this->javascriptRenderer->renderOnShutdownWithHead();
        }
    }

    private function debugMode(): bool
    {
        return DEBUG_FOR_ALL || (DEBUG && $this->user?->isAdmin());
    }
}
