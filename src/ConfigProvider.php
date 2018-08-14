<?php
/**
 * Copyright (c) 2018. Net Glue Ltd
 *
 */

declare(strict_types=1);

namespace NetgluePostmark;

use Zend\ServiceManager\Factory\InvokableFactory;

class ConfigProvider
{

    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
            'postmark' => $this->getPostmarkConfig(),
        ];
    }

    public function getDependencyConfig() : array
    {
        return [
            'factories' => [
                Authentication\Adapter\BasicHttp::class => Container\Authentication\Adapter\BasicHttpFactory::class,
                Controller\WebhookController::class => Container\Controller\WebhookControllerFactory::class,
                Service\EventEmitter::class => InvokableFactory::class,
            ],
        ];
    }

    public function getPostmarkConfig()
    {
        return [
            'webhookOptions' => [
                'basicAuth' => true,
                'username' => null,
                'password' => null,
                'realm'    => 'Postmark',
            ],
        ];
    }
}
