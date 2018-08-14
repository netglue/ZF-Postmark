<?php
declare(strict_types=1);

namespace NetgluePostmark;

use Zend\ModuleManager\Feature;

class Module implements
    Feature\ConfigProviderInterface,
    Feature\ControllerProviderInterface,
    Feature\ServiceProviderInterface
{

    private $configProvider;

    public function __construct()
    {
        $this->configProvider = new ConfigProvider();
    }

    public function getConfig() : array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getControllerConfig() : array
    {
        return [
            'factories' => [
                Controller\WebhookController::class => Container\Controller\WebhookControllerFactory::class,
            ],
        ];
    }

    public function getServiceConfig() : array
    {
        return $this->configProvider->getDependencyConfig();
    }
}
