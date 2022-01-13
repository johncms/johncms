<?php

declare(strict_types=1);

namespace Johncms\Debug;

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBarException;
use Johncms\Debug\Collectors\QueryCollector;
use Johncms\Debug\Collectors\RouteDataCollector;

class DebugBar extends \DebugBar\DebugBar
{
    /**
     * @throws DebugBarException
     */
    public function __construct()
    {
        $this->addCollector(new PhpInfoCollector());
        $this->addCollector(new MessagesCollector());
        $this->addCollector(new RequestDataCollector());
        $this->addCollector(new TimeDataCollector($_SERVER['REQUEST_TIME_FLOAT'] ?? START_TIME));
        $this->addCollector(new MemoryCollector());
        $this->addCollector(new ExceptionsCollector());
        $this->addCollector(new RouteDataCollector());

        $queryCollector = new QueryCollector($this->getTimeCollector());
        $queryCollector->setDataFormatter(new QueryFormatter());
        $queryCollector->setRenderSqlWithParams(true);
        $this->addCollector($queryCollector);

        $this->getJavascriptRenderer('/themes/default/assets/debugbar');
        $this->addCollector(new ConfigCollector(config()));
    }

    public function __invoke(): DebugBar
    {
        return new self();
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    public function getTimeCollector(): TimeDataCollector | DataCollectorInterface
    {
        return $this->getCollector('time');
    }

    public function addBootingTime()
    {
        $timeCollector = $this->getTimeCollector();
        $timeCollector->addMeasure('Booting', $timeCollector->getRequestStartTime(), microtime(true));
    }

    public function startApplicationMeasure()
    {
        $this->getTimeCollector()->startMeasure('application', 'Application');
    }
}
