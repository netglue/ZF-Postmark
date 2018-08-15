<?php
declare(strict_types=1);

namespace NetgluePostmark\Container\Listener;

use NetgluePostmark\Exception;
use NetgluePostmark\Listener\LoggingListener;
use NetgluePostmark\Service\EventEmitter;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LoggingListenerDelegatorFactory
{
    public function __invoke(ContainerInterface $container, string $name, callable $callback) : EventEmitter
    {
        if (! $container->has(LoggerInterface::class)) {
            throw new Exception\ConfigException('A Psr logger cannot be found in the container');
        }
        $emitter = $callback();
        if (! $emitter instanceof EventEmitter) {
            throw new Exception\RuntimeException(sprintf(
                'Expected callback to return an %s instance',
                EventEmitter::class
            ));
        }

        $listener = new LoggingListener($container->get(LoggerInterface::class));
        $listener->attach($emitter->getEventManager());
        return $emitter;
    }
}
