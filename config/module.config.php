<?php

declare(strict_types=1);

return [

    /**
     * For Zend\Mvc apps, change the configuration of these routes if you want to modify the url
     */
    'router' => [
        'routes' => [
            'postmark-outbound-webhook' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/postmark-outbound-webhook',
                    'defaults' => [
                        'controller' => NetgluePostmark\Controller\WebhookController::class,
                        'action' => 'webhook',
                    ],
                ],
            ],
            'postmark-inbound-webhook' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/postmark-inbound-webhook',
                    'defaults' => [
                        'controller' => NetgluePostmark\Controller\WebhookController::class,
                        'action' => 'inbound',
                    ],
                ],
            ],
        ],
    ],

    'postmark' => [
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
    ],

    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],

];
