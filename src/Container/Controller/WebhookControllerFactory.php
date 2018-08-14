<?php
declare(strict_types=1);

namespace NetgluePostmark\Container\Controller;

use NetgluePostmark\Authentication\Adapter\BasicHttp;
use NetgluePostmark\Controller\WebhookController;
use NetgluePostmark\Exception;
use NetgluePostmark\Service\EventEmitter;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use function get_class;
use function is_null;

class WebhookControllerFactory
{
    public function __invoke(ContainerInterface $container) : WebhookController
    {
        $config          = $container->get('config')['postmark']['webhookOptions'];
        $throwExceptions = $config['throwExceptions'];
        $logger          = null;

        if ($container->has(LoggerInterface::class)) {
            $logger = $container->get(LoggerInterface::class);
        }

        if (! is_null($logger) && ! $logger instanceof LoggerInterface) {
            throw new Exception\ConfigException(sprintf(
                'Expected an instance of %s to be returned by the container but got %s',
                LoggerInterface::class,
                get_class($logger)
            ));
        }

        $controller = new WebhookController(
            $container->get(EventEmitter::class),
            $throwExceptions,
            $logger
        );

        if (true === $config['basicAuth']) {
            $controller->setBasicAuth($container->get(BasicHttp::class));
        }

        return $controller;
    }
}
