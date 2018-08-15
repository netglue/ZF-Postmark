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
            'delegators' => [
                Service\EventEmitter::class => [
                    // To enable the example logging listener by default, you'd uncomment this
                    //Container\Listener\LoggingListenerDelegatorFactory::class,
                ],
            ],
        ];
    }

    public function getPostmarkConfig()
    {
        return [
            'webhookOptions' => [
                /**
                 * Basic Auth.
                 * If basic auth is enabled, you must provide a string username and password, in the clear.
                 * You'd then setup your Postmark webhook like: https://postmarkUser:Password@mydomain.com/webhookUrl
                 * The Realm is usually presented in a login dialog
                 */
                'basicAuth' => true,
                'username' => null,
                'password' => null,
                'realm'    => 'Postmark',
                /**
                 * Exception handling for the endpoint
                 * When true, the webhook controller will wrap and throw any exceptions that occur
                 * during execution. This will then go through whatever exception handling is setup on your app, such
                 * as displaying an error page etc.
                 * When false, a JSON response is returned with 500 status code (Postmark will retry later), and, if a
                 * \Psr\Log\LoggerInterface is available in the container, the exception will be logged.
                 */
                'throwExceptions' => false,
            ],
        ];
    }
}
