<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Console;

use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleApp
{
    protected Application $application;

    public function __construct()
    {
        $this->application = new Application();
    }

    protected function collectCommands(): void
    {
        $commands = di('config')['commands'];
        foreach ($commands as $command) {
            $this->application->add(di($command));
        }
    }

    /**
     * @throws Exception
     */
    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        $this->collectCommands();
        return $this->application->run($input, $output);
    }
}
