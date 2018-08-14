<?php
declare(strict_types=1);

namespace NetgluePostmark\Container\Controller;

use NetgluePostmark\Authentication\Adapter\BasicHttp;
use NetgluePostmark\Controller\WebhookController;
use NetgluePostmark\Service\EventEmitter;
use Psr\Container\ContainerInterface;

class WebhookControllerFactory
{
    public function __invoke(ContainerInterface $container) : WebhookController
    {
        $controller = new WebhookController($container->get(EventEmitter::class));

        $config = $container->get('config')['postmark']['webhookOptions'];
        if (true === $config['basicAuth']) {
            $controller->setBasicAuth($container->get(BasicHttp::class));
        }

        return $controller;
    }
}
