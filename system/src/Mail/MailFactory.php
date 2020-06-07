<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Mail;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Psr\Container\ContainerInterface;

class MailFactory extends Message
{
    /** @var TransportInterface */
    private $transport;

    public function __invoke(ContainerInterface $container): Message
    {
        $config = di('config')['mail'];
        $this->transport = TransportFactory::create(
            [
                'type'    => $config['transport'],
                'options' => $config['options'][$config['transport']] ?? null,
            ]
        );

        // Set default sender
        $site_config = di('config')['johncms'];
        $this->setFrom($site_config['email'], $site_config['copyright']);

        $this->setEncoding('utf-8');

        return $this;
    }

    public function send()
    {
        return $this->transport->send($this);
    }
}
